<?php

namespace App\Services;

use App\Models\BotTransaction;
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
        if ($row->type === TransactionTaxonomy::TYPE_TAX
            || $row->type === TransactionTaxonomy::TYPE_RECEIVABLE_OUT
            || $row->type === TransactionTaxonomy::TYPE_RECEIVABLE_IN
            || $row->type === TransactionTaxonomy::TYPE_PAYABLE_IN
            || $row->type === TransactionTaxonomy::TYPE_PAYABLE_OUT) {
            return null;
        }

        $household = $this->resolveHouseholdDurable($row);
        if ($household !== null) {
            return $household;
        }

        $fromDb = $this->mappingService->resolveBucket($row);
        if ($fromDb !== null) {
            return $this->normalizeBucket($fromDb);
        }

        return $this->resolveLegacy($row);
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
            'gym', 'pilates', 'yoga', 'crossfit', 'personal trainer', 'coaching tenis',
            'coaching padel', 'les renang', 'tenis', 'renang', 'padel',
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
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
