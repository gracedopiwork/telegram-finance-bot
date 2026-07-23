<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\CategoryBucketMapping;
use App\Support\TransactionTaxonomy;
use Illuminate\Support\Facades\Schema;

/**
 * Selesaikan kategori transaksi ke closed list YFD AI Taxonomy.
 * Tidak membuat kategori baru di luar daftar resmi.
 */
class CategoryAutoRegisterService
{
    public function __construct(
        private readonly BotCategoryRulesService $categoryRulesService,
        private readonly CategoryBucketMappingService $mappingService,
        private readonly CategoryBucketService $bucketService,
    ) {}

    public function resolveOrRegister(
        string $categoryInput,
        string $type,
        string $nature,
        string $notes = '',
    ): string {
        $resolved = $this->resolveWithoutRegister($categoryInput, $type);
        $this->ensureMappingExists($resolved, $type, $nature, $notes);

        return $resolved;
    }

    /**
     * Canonicalize a category for preview without writing a mapping to the database.
     */
    public function resolveWithoutRegister(string $categoryInput, string $type): string
    {
        $rules = $this->categoryRulesService->export();
        $categoryInput = trim($categoryInput);
        $fallback = (string) ($rules['fallback_category'] ?? config('yfd_taxonomy.fallback_category', 'Lain-lain'));

        if ($categoryInput === '') {
            return $this->defaultCategoryForType($type, $fallback);
        }

        $existing = $this->resolveExisting($categoryInput, $rules);
        if ($existing !== null) {
            return $existing;
        }

        return $this->defaultCategoryForType($type, $fallback);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function resolveExisting(string $value, array $rules): ?string
    {
        $aliases = array_merge(
            (array) config('yfd_taxonomy.aliases', []),
            (array) ($rules['aliases'] ?? []),
        );
        $normalized = $this->normalizeAliasKey($value);
        if ($normalized !== '' && isset($aliases[$normalized])) {
            return (string) $aliases[$normalized];
        }

        foreach ((array) ($rules['categories'] ?? []) as $cat) {
            if ($this->categoryKeysMatch((string) $cat, $value)) {
                return (string) $cat;
            }
        }

        return null;
    }

    private function ensureMappingExists(string $name, string $type, string $nature, string $notes): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        $existing = CategoryBucketMapping::query()
            ->whereRaw('LOWER(TRIM(category)) = ?', [mb_strtolower(trim($name))])
            ->first();

        if ($existing !== null) {
            $this->mergeKeywordsFromNotes($existing, $notes);

            return;
        }

        $txType = TransactionTaxonomy::mappingTransactionType($type);
        $bucket = $this->inferBucket($name, $type, $nature, $notes);
        $maxSort = (int) CategoryBucketMapping::query()->max('sort_order');

        CategoryBucketMapping::query()->create([
            'category' => $name,
            'sub_category' => '-',
            'bucket' => $bucket,
            'transaction_type' => $txType,
            'nature' => $nature !== '' ? $nature : null,
            'match_keywords' => $this->keywordsForAutoCategory($name, $notes),
            'reason' => 'Sinkron taxonomy resmi YFD',
            'sort_order' => $maxSort + 1,
            'is_active' => true,
        ]);

        $this->mappingService->forgetCache();
    }

    private function mergeKeywordsFromNotes(CategoryBucketMapping $mapping, string $notes): void
    {
        $extra = $this->keywordsFromNotes($notes);
        if ($extra === []) {
            return;
        }

        $current = $mapping->keywordsList();
        $merged = array_values(array_unique(array_merge($current, $extra)));
        if (count($merged) === count($current)) {
            return;
        }

        $mapping->update([
            'match_keywords' => implode(',', array_slice($merged, 0, 50)),
        ]);
        $this->mappingService->forgetCache();
    }

    private function inferBucket(string $name, string $type, string $nature, string $notes): string
    {
        if ($type === TransactionTaxonomy::TYPE_INCOME) {
            return 'Income';
        }

        if ($type === TransactionTaxonomy::TYPE_SAVING) {
            return 'Future Building';
        }

        $stub = new BotTransaction([
            'type' => $type,
            'category' => $name,
            'sub_category' => '-',
            'nature' => $nature,
            'notes' => $notes,
        ]);

        $resolved = $this->bucketService->resolve($stub);
        if ($resolved !== null) {
            return $resolved;
        }

        return $nature === TransactionTaxonomy::NATURE_WANTS
            ? 'Flexible + Social'
            : 'Essential Living';
    }

    private function keywordsForAutoCategory(string $name, string $notes = ''): string
    {
        $compact = $this->compactKey($name);
        $keywords = array_values(array_unique(array_filter(array_merge(
            [
                mb_strtolower(trim($name)),
                $compact !== mb_strtolower(trim($name)) ? $compact : '',
            ],
            $this->keywordsFromNotes($notes),
        ))));

        return implode(',', array_slice($keywords, 0, 50));
    }

    /**
     * @return list<string>
     */
    private function keywordsFromNotes(string $notes): array
    {
        $text = mb_strtolower(trim($notes));
        if ($text === '') {
            return [];
        }

        $text = preg_replace('/\d+([.,]\d+)?\s*(rb|ribu|jt|juta|k|rp)?/u', ' ', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;

        $stop = [
            'beli', 'bayar', 'di', 'ke', 'dari', 'yang', 'untuk', 'dengan', 'dan', 'atau',
            'ini', 'itu', 'ada', 'sudah', 'bisa', 'masuk', 'setelah', 'sebelum', 'karena',
            'hari', 'tanggal', 'tgl', 'bulan', 'tahun', 'rp', 'rupiah', 'nominal', 'harga',
        ];

        $tokens = preg_split('/\s+/u', $text) ?: [];
        $out = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if (mb_strlen($token) < 3 || in_array($token, $stop, true)) {
                continue;
            }
            if (preg_match('/^\d+$/', $token)) {
                continue;
            }
            $out[] = $token;
        }

        return array_values(array_unique(array_slice($out, 0, 12)));
    }

    private function defaultCategoryForType(string $type, string $fallback): string
    {
        if ($type === TransactionTaxonomy::TYPE_INCOME) {
            return 'Gaji';
        }
        if ($type === TransactionTaxonomy::TYPE_SAVING) {
            return 'Investasi & Tabungan';
        }

        return $fallback !== '' ? $fallback : 'Lain-lain';
    }

    private function normalizeAliasKey(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['_', '-'], ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        return trim($v);
    }

    private function compactKey(string $value): string
    {
        return preg_replace('/\s+/', '', $this->normalizeAliasKey($value)) ?? '';
    }

    private function categoryKeysMatch(string $a, string $b): bool
    {
        if (mb_strtolower(trim($a)) === mb_strtolower(trim($b))) {
            return true;
        }

        return $this->compactKey($a) === $this->compactKey($b) && $this->compactKey($a) !== '';
    }
}
