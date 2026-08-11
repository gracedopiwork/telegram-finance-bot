<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\CategoryBucketMapping;
use App\Support\TransactionTaxonomy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CategoryBucketMappingService
{
    public function usesDatabase(): bool
    {
        return Schema::hasTable('category_bucket_mappings')
            && CategoryBucketMapping::query()->where('is_active', true)->exists();
    }

    /**
     * @return Collection<int, CategoryBucketMapping>
     */
    public function activeMappings(): Collection
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return collect();
        }

        return Cache::remember('category_bucket_mappings:active', now()->addMinutes(10), function () {
            return CategoryBucketMapping::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget('category_bucket_mappings:active');
    }

    /**
     * Cocokkan transaksi ke bucket dari tabel admin. Null = pakai aturan legacy.
     */
    public function resolveBucket(BotTransaction $row): ?string
    {
        if (! $this->usesDatabase()) {
            return null;
        }

        $category = mb_strtolower(trim((string) $row->category));
        $sub = mb_strtolower(trim((string) $row->sub_category));
        $nature = trim((string) $row->nature);
        $combined = mb_strtolower(trim("{$row->notes} {$category}"));
        $txType = TransactionTaxonomy::mappingTransactionType((string) $row->type);

        foreach ($this->activeMappings() as $mapping) {
            if ($mapping->transaction_type !== $txType && $mapping->transaction_type !== 'transfer') {
                continue;
            }

            if ($mapping->nature && $mapping->nature !== $nature) {
                continue;
            }

            $mapCategory = mb_strtolower(trim($mapping->category));
            $mapSub = mb_strtolower(trim((string) $mapping->sub_category));
            $wildcardCategory = $mapCategory === '*';

            $categoryMatch = $wildcardCategory
                || ($mapCategory !== '' && $this->categoryKeysMatch($mapCategory, $category));
            $subMatch = $mapSub === '' || $mapSub === '-' || in_array($sub, ['', '-'], true) || $mapSub === $sub;

            $keywords = $mapping->keywordsList();
            $hasKeywords = $keywords !== [];
            $keywordMatch = false;
            foreach ($keywords as $keyword) {
                if (\App\Support\KeywordMatch::contains($combined, $keyword)) {
                    $keywordMatch = true;
                    break;
                }
            }

            // Wildcard tanpa keyword boleh menjadi default untuk nature/type tersebut.
            // Wildcard kontekstual wajib menunggu keyword agar tidak menimpa semua transaksi.
            if ($wildcardCategory && ! $hasKeywords) {
                return $mapping->bucket;
            }

            // Keyword spesifik menang sebelum match kategori generik.
            if ($keywordMatch && ($wildcardCategory || $categoryMatch)) {
                return $mapping->bucket;
            }

            if ($mapCategory !== '' && ! $wildcardCategory && $categoryMatch && $subMatch && ! $hasKeywords) {
                return $mapping->bucket;
            }
        }

        // Mapping yang punya keyword tidak boleh berubah menjadi default kategori ketika
        // keyword-nya tidak cocok. Biarkan resolver legacy menentukan dari konteks lain.
        return null;
    }

    private function categoryKeysMatch(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $compactA = preg_replace('/\s+/', '', $a) ?? '';
        $compactB = preg_replace('/\s+/', '', $b) ?? '';

        return $compactA !== '' && $compactA === $compactB;
    }
}
