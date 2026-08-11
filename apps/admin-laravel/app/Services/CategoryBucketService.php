<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Support\KeywordMatch;
use App\Support\TransactionTaxonomy;

class CategoryBucketService
{
    public function __construct(
        private readonly CategoryBucketMappingService $mappingService,
    ) {}

    /**
     * Resolve ke salah satu bucket prescription (4 bucket) atau null jika dikecualikan.
     */
    public function resolve(BotTransaction $row): ?string
    {
        if ($row->type === TransactionTaxonomy::TYPE_INCOME
            || $row->type === TransactionTaxonomy::TYPE_TAX
            || $row->type === TransactionTaxonomy::TYPE_RECEIVABLE_OUT
            || $row->type === TransactionTaxonomy::TYPE_RECEIVABLE_IN
            || $row->type === TransactionTaxonomy::TYPE_PAYABLE_IN
            || $row->type === TransactionTaxonomy::TYPE_PAYABLE_OUT) {
            return null;
        }

        // Urutan eksklusif — jangan sampai Proteksi/mapping menelan konteks lain.
        $beauty = $this->resolveBeautyCare($row);
        if ($beauty !== null) {
            return $beauty;
        }
        $household = $this->resolveHouseholdDurable($row);
        if ($household !== null) {
            return $household;
        }
        $gym = $this->resolvePaidSport($row);
        if ($gym !== null) {
            return $gym;
        }
        $selfDev = $this->resolveSelfDevelopment($row);
        if ($selfDev !== null) {
            return $selfDev;
        }

        $fromDb = $this->mappingService->resolveBucket($row);
        if ($fromDb !== null) {
            return $this->normalizeBucket($fromDb);
        }

        return $this->resolveLegacy($row);
    }

    /**
     * Makeup/skincare selalu Flexible — bukan Essential, bukan Protection.
     */
    private function resolveBeautyCare(BotTransaction $row): ?string
    {
        if ($row->type !== TransactionTaxonomy::TYPE_EXPENSE) {
            return null;
        }
        $combined = mb_strtolower(trim("{$row->notes} {$row->category}"));
        $markers = [
            'makeup', 'make up', 'make-up', 'skincare', 'skin care', 'dandan',
            'lipstik', 'lipstick', 'mascara', 'foundation', 'cushion',
            'maybelline', 'maybeline', 'parfum', 'facial', 'toner wajah', 'toner',
            'sunscreen', 'serum', 'moisturizer', 'pelembab', 'spa', 'potong rambut',
        ];
        if (! KeywordMatch::containsAny($combined, $markers)) {
            return null;
        }

        return 'Flexible + Social';
    }

    /**
     * Gym/olahraga berbayar = Flexible. Grab/ojek ke gym tetap Transport (mapping).
     */
    private function resolvePaidSport(BotTransaction $row): ?string
    {
        if ($row->type !== TransactionTaxonomy::TYPE_EXPENSE) {
            return null;
        }
        $combined = mb_strtolower(trim("{$row->notes} {$row->category}"));
        if (KeywordMatch::containsAny($combined, ['grab', 'gojek', 'ojek', 'maxim', 'grabbike', 'grabcar'])) {
            return null;
        }
        $sports = [
            'gym', 'yoga', 'pilates', 'crossfit', 'personal trainer',
            'membership gym',
        ];
        if (! KeywordMatch::containsAny($combined, $sports)) {
            return null;
        }

        return 'Flexible + Social';
    }

    /**
     * Seminar/buku pengembangan/les keterampilan = Future Building.
     * SPP/uang sekolah tetap Essential (mapping sort 21).
     */
    private function resolveSelfDevelopment(BotTransaction $row): ?string
    {
        if ($row->type !== TransactionTaxonomy::TYPE_EXPENSE) {
            return null;
        }
        $combined = mb_strtolower(trim("{$row->notes} {$row->category}"));
        $wajib = ['spp', 'ukt', 'uang sekolah', 'uang kuliah', 'buku pelajaran'];
        if (KeywordMatch::containsAny($combined, $wajib)) {
            return null;
        }
        $markers = [
            'seminar', 'workshop', 'sertifikasi', 'conference', 'pengembangan diri',
            'self development', 'coaching karier', 'public speaking', 'les piano',
            'les musik', 'les bahasa', 'psychology of money', 'buku finansial',
            'buku financial', 'iuran idi', 'bayar idi', 'iuran organisasi',
            'mentoring', 'piano untuk belajar',
        ];
        if (! KeywordMatch::containsAny($combined, $markers)) {
            return null;
        }

        return 'Future Building';
    }

    /**
     * Tumbler/perabot bukan Proteksi — ganti rusak = Essential, koleksi = Flexible.
     */
    private function resolveHouseholdDurable(BotTransaction $row): ?string
    {
        if ($row->type !== TransactionTaxonomy::TYPE_EXPENSE) {
            return null;
        }

        $combined = mb_strtolower(trim("{$row->notes} {$row->category}"));
        $items = [
            'tumbler', 'termos', 'kulkas', 'rice cooker', 'mesin cuci',
            'gorden', 'sprei', 'piring', 'perabot',
        ];
        if (! $this->containsAny($combined, $items)) {
            return null;
        }

        $repair = [
            'rusak', 'pecah', 'bocor', 'ganti yang', 'ganti yg', 'mengganti',
            'belum memadai', 'tidak layak', 'sebelumnya rusak',
        ];
        $lifestyle = ['koleksi', 'ikuti tren', 'ikut tren', 'upgrade', 'fomo', 'tambah koleksi'];

        if ($this->containsAny($combined, $repair) || (string) $row->nature === 'Need') {
            if ($this->containsAny($combined, $lifestyle) && ! $this->containsAny($combined, $repair)) {
                return 'Flexible + Social';
            }

            return 'Essential Living';
        }

        return 'Flexible + Social';
    }

    private function resolveLegacy(BotTransaction $row): ?string
    {
        $nature = (string) $row->nature;
        $category = mb_strtolower((string) $row->category);
        $notes = mb_strtolower((string) $row->notes);
        $combined = "{$notes} {$category}";

        if ($row->type === TransactionTaxonomy::TYPE_INCOME
            || $row->type === TransactionTaxonomy::TYPE_TAX
            || $row->type === TransactionTaxonomy::TYPE_RECEIVABLE_OUT
            || $row->type === TransactionTaxonomy::TYPE_RECEIVABLE_IN
            || $row->type === TransactionTaxonomy::TYPE_PAYABLE_IN
            || $row->type === TransactionTaxonomy::TYPE_PAYABLE_OUT) {
            return null;
        }

        if (in_array($category, ['piutang keluar', 'piutang masuk', 'utang masuk', 'utang keluar', 'hutang masuk', 'hutang keluar'], true)) {
            return null;
        }

        // Dana darurat berfungsi sebagai proteksi, walaupun jenis transaksinya Saving/Investment.
        if ($this->containsAny($combined, config('category_buckets.protection_keywords', []))) {
            return 'Protection';
        }

        if ($row->type === TransactionTaxonomy::TYPE_SAVING) {
            return 'Future Building';
        }

        if ($this->containsAny($combined, config('category_buckets.future_building_context_keywords', []))) {
            return 'Future Building';
        }
        if ($this->matchesCategory($category, ['transport', 'transportasi'])
            && $this->containsAny($combined, config('category_buckets.transport_flexible_keywords', []))) {
            return 'Flexible + Social';
        }
        if ($this->containsAny($combined, config('category_buckets.essential_context_keywords', []))) {
            return 'Essential Living';
        }

        if ($this->matchesCategory($category, ['social', 'sosial & keluarga', 'hadiah', 'lifestyle & hiburan', 'traveling'])) {
            return 'Flexible + Social';
        }

        // Olahraga berbayar selalu Flexible — jangan kena keyword "les/coaching" Future Building.
        if ($this->containsAny($combined, [
            'gym', 'pilates', 'yoga', 'crossfit', 'personal trainer',
            'coaching tenis', 'coaching padel', 'les renang', 'tenis', 'renang', 'padel',
        ])) {
            return 'Flexible + Social';
        }

        if ($this->containsAny($combined, config('category_buckets.future_building_keywords', []))) {
            return 'Future Building';
        }
        if ($nature === 'Wants' || $this->containsAny($combined, config('category_buckets.flexible_keywords', []))) {
            return 'Flexible + Social';
        }
        if ($this->matchesCategory($category, config('category_buckets.essential_categories', []))) {
            return 'Essential Living';
        }

        return 'Essential Living';
    }

    private function normalizeBucket(string $bucket): ?string
    {
        if ($bucket === 'Transfer (Excluded)' || $bucket === 'Income') {
            return null;
        }

        return $bucket;
    }

    /**
     * @param  list<string>  $allowed
     */
    private function matchesCategory(string $category, array $allowed): bool
    {
        foreach ($allowed as $item) {
            if ($category === mb_strtolower($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $keywords
     */
    private function containsAny(string $haystack, array $keywords): bool
    {
        return KeywordMatch::containsAny($haystack, $keywords);
    }
}
