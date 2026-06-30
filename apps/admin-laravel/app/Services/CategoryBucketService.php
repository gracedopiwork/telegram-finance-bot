<?php

namespace App\Services;

use App\Models\BotTransaction;

class CategoryBucketService
{
    public function resolve(BotTransaction $row): string
    {
        $nature = (string) $row->nature;
        $category = mb_strtolower((string) $row->category);
        $sub = mb_strtolower((string) $row->sub_category);
        $notes = mb_strtolower((string) $row->notes);
        $combined = "{$sub} {$notes} {$category}";

        if ($nature === 'Saving/Investement') {
            return 'Future Building';
        }
        if ($nature === 'Donation' || $category === 'social') {
            return 'Flexible + Social';
        }

        if ($this->containsAny($combined, config('category_buckets.protection_keywords', []))) {
            return 'Protection';
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
        if ($this->matchesCategory($category, ['listrik', 'air'])) {
            return 'Protection';
        }

        return 'Essential Living';
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
