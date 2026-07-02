<?php

namespace App\Services;

use App\Models\CategoryBucketMapping;
use Illuminate\Support\Facades\Schema;

class BotCategoryRulesService
{
    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return $this->staticFallback();
        }

        $mappings = CategoryBucketMapping::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($mappings->isEmpty()) {
            return $this->staticFallback();
        }

        $categories = [];
        $subCategories = [];
        /** @var array<string, list<string>> $categorySubMap */
        $categorySubMap = [];
        $rules = [];

        foreach ($mappings as $mapping) {
            $rules[] = [
                'category' => $mapping->category,
                'sub_category' => $mapping->sub_category,
                'bucket' => $mapping->bucket,
                'transaction_type' => $mapping->transaction_type,
                'nature' => $mapping->nature,
                'keywords' => $mapping->keywordsList(),
                'reason' => $mapping->reason,
                'sort_order' => (int) $mapping->sort_order,
            ];

            if ($mapping->category === '*') {
                continue;
            }

            $categories[] = $mapping->category;

            if ($mapping->sub_category) {
                $subCategories[] = $mapping->sub_category;
                $categorySubMap[$mapping->category][] = $mapping->sub_category;
            }
        }

        foreach ($categorySubMap as $category => $subs) {
            $categorySubMap[$category] = array_values(array_unique($subs));
        }

        $version = md5($mappings->max('updated_at')?->toJSON() ?? '0');

        return [
            'version' => $version,
            'updated_at' => $mappings->max('updated_at')?->toIso8601String(),
            'categories' => array_values(array_unique($categories)),
            'sub_categories' => array_values(array_unique($subCategories)),
            'category_sub_map' => $categorySubMap,
            'rules' => $rules,
            'fallback_category' => (string) config('category_buckets.bot_fallback_category', 'Jajan'),
            'fallback_sub' => (string) config('category_buckets.bot_fallback_sub', 'Pengeluaran lain-lain'),
            'natures' => ['Need', 'Wants', 'Saving/Investement', 'Donation'],
            'source' => 'database',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staticFallback(): array
    {
        $defaults = (array) config('category_bucket_mappings_defaults', []);
        $categories = [];
        $subCategories = [];
        $categorySubMap = [];
        $rules = [];

        foreach ($defaults as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rules[] = [
                'category' => $row['category'] ?? '',
                'sub_category' => $row['sub_category'] ?? null,
                'bucket' => $row['bucket'] ?? '',
                'transaction_type' => $row['transaction_type'] ?? 'expense',
                'nature' => $row['nature'] ?? null,
                'keywords' => array_filter(array_map('trim', explode(',', (string) ($row['match_keywords'] ?? '')))),
                'reason' => $row['reason'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];

            $cat = (string) ($row['category'] ?? '');
            $sub = $row['sub_category'] ?? null;
            if ($cat === '' || $cat === '*') {
                continue;
            }
            $categories[] = $cat;
            if (is_string($sub) && $sub !== '') {
                $subCategories[] = $sub;
                $categorySubMap[$cat][] = $sub;
            }
        }

        foreach ($categorySubMap as $category => $subs) {
            $categorySubMap[$category] = array_values(array_unique($subs));
        }

        return [
            'version' => 'config-fallback',
            'updated_at' => null,
            'categories' => array_values(array_unique($categories)),
            'sub_categories' => array_values(array_unique($subCategories)),
            'category_sub_map' => $categorySubMap,
            'rules' => $rules,
            'fallback_category' => (string) config('category_buckets.bot_fallback_category', 'Jajan'),
            'fallback_sub' => (string) config('category_buckets.bot_fallback_sub', 'Pengeluaran lain-lain'),
            'natures' => ['Need', 'Wants', 'Saving/Investement', 'Donation'],
            'source' => 'config',
        ];
    }
}
