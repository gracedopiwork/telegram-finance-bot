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
        $fromDb = $this->mappingService->resolveBucket($row);
        if ($fromDb !== null) {
            return $this->normalizeBucket($fromDb);
        }

        return $this->resolveLegacy($row);
    }

    private function resolveLegacy(BotTransaction $row): ?string
    {
        $nature = (string) $row->nature;
        $category = mb_strtolower((string) $row->category);
        $notes = mb_strtolower((string) $row->notes);
        $combined = "{$notes} {$category}";

        if ($row->type === TransactionTaxonomy::TYPE_INCOME) {
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
        if ($this->containsAny($combined, config('category_buckets.essential_context_keywords', []))) {
            return 'Essential Living';
        }
        if ($this->matchesCategory($category, ['makan', 'jajan'])
            && $this->containsAny($combined, config('category_buckets.essential_meeting_keywords', []))) {
            return 'Essential Living';
        }

        if ($category === 'social') {
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
