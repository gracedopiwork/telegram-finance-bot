<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\CategoryBucketMapping;
use App\Support\TransactionTaxonomy;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Selesaikan kategori transaksi: cocokkan yang sudah ada, atau buat pemetaan bucket baru otomatis.
 */
class CategoryAutoRegisterService
{
    /** @var array<string, string> */
    private const CATEGORY_ALIASES = [
        'makanan' => 'Makan',
        'makan' => 'Makan',
        'muen' => 'Makan',
        'grab/maxime' => 'Transport',
        'grab' => 'Transport',
        'maxime' => 'Transport',
        'gojek' => 'Transport',
        'ojek' => 'Transport',
        'sosial' => 'Social',
        'hiburan' => 'Social',
        'networking' => 'Social',
        'yfd' => 'Social',
        'admin bank' => 'Jajan',
        'skin care-beauty' => 'Skincare',
        'skin care' => 'Skincare',
        'skincare' => 'Skincare',
        'beauty' => 'Skincare',
        'subscription' => 'Subscription',
        'langganan' => 'Subscription',
        'asuransi' => 'Asuransi',
        'premi asuransi' => 'Asuransi',
        'premi' => 'Asuransi',
        'bpjs' => 'Asuransi',
        'dividen' => 'Dividen',
        'dividend' => 'Dividen',
        'lain-lain' => 'Jajan',
        'lain lain' => 'Jajan',
        'dipinjam' => 'Social',
    ];

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
        $rules = $this->categoryRulesService->export();
        $categoryInput = trim($categoryInput);

        if ($categoryInput === '') {
            return $this->defaultCategoryForType($type, $rules);
        }

        $existing = $this->resolveExisting($categoryInput, $rules);
        if ($existing !== null) {
            $this->registerIfMissing($existing, $type, $nature, $notes);

            return $existing;
        }

        $newName = $this->formatCategoryName($categoryInput);
        if ($newName === '') {
            return $this->defaultCategoryForType($type, $rules);
        }

        $this->registerIfMissing($newName, $type, $nature, $notes);

        return $newName;
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function resolveExisting(string $value, array $rules): ?string
    {
        $normalized = $this->normalizeAliasKey($value);
        if ($normalized !== '' && isset(self::CATEGORY_ALIASES[$normalized])) {
            $target = self::CATEGORY_ALIASES[$normalized];
            foreach ($rules['categories'] as $cat) {
                if ($this->categoryKeysMatch((string) $cat, $target)) {
                    return (string) $cat;
                }
            }

            return $target;
        }

        foreach ($rules['categories'] as $cat) {
            if ($this->categoryKeysMatch((string) $cat, $value)) {
                return (string) $cat;
            }
        }

        $needle = $this->compactKey($value);
        if ($needle === '') {
            return null;
        }

        /** @var array<string, list<string>> $categorySubMap */
        $categorySubMap = $rules['category_sub_map'] ?? [];
        foreach ($categorySubMap as $category => $subs) {
            foreach ($subs as $sub) {
                if ($this->categoryKeysMatch((string) $sub, $value)) {
                    return (string) $category;
                }
            }
        }

        foreach ($rules['rules'] as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $sub = trim((string) ($rule['sub_category'] ?? ''));
            $cat = trim((string) ($rule['category'] ?? ''));
            if ($sub !== '' && $cat !== '' && $cat !== '*' && $this->categoryKeysMatch($sub, $value)) {
                return $cat;
            }
        }

        return null;
    }

    private function registerIfMissing(string $name, string $type, string $nature, string $notes): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        $exists = CategoryBucketMapping::query()
            ->whereRaw('LOWER(TRIM(category)) = ?', [mb_strtolower(trim($name))])
            ->exists();

        if ($exists) {
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
            'match_keywords' => $this->keywordsForAutoCategory($name),
            'reason' => 'Dibuat otomatis dari transaksi/import',
            'sort_order' => $maxSort + 1,
            'is_active' => true,
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

        $txType = TransactionTaxonomy::mappingTransactionType($type);

        return match ($txType) {
            'income' => 'Income',
            'saving' => 'Future Building',
            default => $nature === TransactionTaxonomy::NATURE_WANTS
                ? 'Flexible + Social'
                : 'Essential Living',
        };
    }

    private function keywordsForAutoCategory(string $name): string
    {
        $compact = $this->compactKey($name);
        $keywords = array_values(array_unique(array_filter([
            mb_strtolower(trim($name)),
            $compact !== mb_strtolower(trim($name)) ? $compact : '',
        ])));

        return implode(',', $keywords);
    }

  /**
   * @param  array<string, mixed>  $rules
   */
    private function defaultCategoryForType(string $type, array $rules): string
    {
        if ($type === TransactionTaxonomy::TYPE_INCOME) {
            return 'Gaji';
        }

        return (string) ($rules['fallback_category'] ?? 'Jajan');
    }

    private function formatCategoryName(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return Str::title($value);
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
