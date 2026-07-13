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
        'affiliate' => 'Affiliate',
        'afiliasi' => 'Affiliate',
        'komisi' => 'Affiliate',
        'commission' => 'Affiliate',
        'referral' => 'Affiliate',
        'shopee affiliate' => 'Affiliate',
        'tiktok affiliate' => 'Affiliate',
        'tokopedia affiliate' => 'Affiliate',
        'lazada affiliate' => 'Affiliate',
        'bunga' => 'Bunga Investasi',
        'bunga investasi' => 'Bunga Investasi',
        'bunga deposito' => 'Bunga Investasi',
        'bunga tabungan' => 'Bunga Investasi',
        'interest' => 'Bunga Investasi',
        'interest income' => 'Bunga Investasi',
        'cashback' => 'Cashback',
        'cash back' => 'Cashback',
        'refund' => 'Refund',
        'pengembalian dana' => 'Refund',
        'freelance' => 'Freelance',
        'freelancer' => 'Freelance',
        'honor' => 'Freelance',
        'honorarium' => 'Freelance',
        'bonus' => 'Bonus',
        'thr' => 'Bonus',
        'penjualan' => 'Penjualan',
        'hasil jualan' => 'Penjualan',
        'sewa masuk' => 'Sewa Masuk',
        'transfer masuk' => 'Transfer Masuk',
        'kesehatan' => 'Kesehatan',
        'obat' => 'Kesehatan',
        'pendidikan' => 'Pendidikan',
        'spp' => 'Pendidikan',
        'komunikasi' => 'Komunikasi',
        'pulsa' => 'Komunikasi',
        'kuota' => 'Komunikasi',
        'cicilan' => 'Cicilan',
        'angsuran' => 'Cicilan',
        'pajak' => 'Pajak',
        'saham' => 'Saham',
        'reksadana' => 'Reksadana',
        'lain-lain' => 'Jajan',
        'lain lain' => 'Jajan',
        'elektronik' => 'Elektronik',
        'gadget' => 'Elektronik',
        'headset' => 'Elektronik',
        'earphone' => 'Elektronik',
        'laptop' => 'Elektronik',
        'hp' => 'Elektronik',
        'handphone' => 'Elektronik',
        'tumbler' => 'Peralatan',
        'botol minum' => 'Peralatan',
        'peralatan' => 'Peralatan',
        'rumah tangga' => 'Peralatan',
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
            'reason' => 'Dibuat otomatis dari transaksi/import/bot',
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

        $txType = TransactionTaxonomy::mappingTransactionType($type);

        return match ($txType) {
            'income' => 'Income',
            'saving' => 'Future Building',
            default => $nature === TransactionTaxonomy::NATURE_WANTS
                ? 'Flexible + Social'
                : 'Essential Living',
        };
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

        if ($this->categoryKeysMatch($name, 'Affiliate')) {
            $keywords = array_values(array_unique(array_merge($keywords, [
                'affiliate',
                'afiliasi',
                'komisi',
                'commission',
                'referral',
                'shopee affiliate',
                'tiktok affiliate',
            ])));
        }

        if ($this->categoryKeysMatch($name, 'Bunga Investasi')) {
            $keywords = array_values(array_unique(array_merge($keywords, [
                'bunga',
                'bunga investasi',
                'bunga deposito',
                'bunga tabungan',
                'terima bunga',
                'dapat bunga',
                'interest',
                'interest income',
            ])));
        }

        if ($this->categoryKeysMatch($name, 'Peralatan')) {
            $keywords = array_values(array_unique(array_merge($keywords, [
                'tumbler',
                'botol minum',
                'peralatan',
                'rumah tangga',
            ])));
        }

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
            'happy', 'senang', 'sedih', 'capek', 'lelah', 'rawat', 'kerja', 'setelah',
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
