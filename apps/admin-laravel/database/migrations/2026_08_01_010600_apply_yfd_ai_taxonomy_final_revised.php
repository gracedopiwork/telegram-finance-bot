<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sync ke YFD AI Taxonomy FINAL REVISED — tambah Pakaian & Aksesoris + mapping laundry/cicilan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'laundry' => 'Kesehatan & Kebersihan Diri',
            'cuci baju' => 'Kesehatan & Kebersihan Diri',
            'dry clean' => 'Kesehatan & Kebersihan Diri',
            'skincare' => 'Kesehatan & Kebersihan Diri',
            'serum' => 'Kesehatan & Kebersihan Diri',
            'fashion' => 'Pakaian & Aksesoris',
            'pakaian' => 'Pakaian & Aksesoris',
            'baju' => 'Pakaian & Aksesoris',
            'sepatu' => 'Pakaian & Aksesoris',
            'aksesoris' => 'Pakaian & Aksesoris',
            'seragam' => 'Pakaian & Aksesoris',
        ];

        if (Schema::hasTable('bot_transactions')) {
            foreach ($map as $from => $to) {
                DB::table('bot_transactions')
                    ->whereRaw('LOWER(TRIM(category)) = ?', [$from])
                    ->update(['category' => $to]);
            }
        }

        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        foreach ($map as $from => $to) {
            DB::table('category_bucket_mappings')
                ->whereRaw('LOWER(TRIM(category)) = ?', [$from])
                ->update(['category' => $to]);
        }

        $official = array_map(
            'mb_strtolower',
            array_merge(
                (array) config('yfd_taxonomy.expense_categories', []),
                (array) config('yfd_taxonomy.income_categories', []),
                ['*'],
            ),
        );

        foreach (DB::table('category_bucket_mappings')->select(['id', 'category'])->get() as $row) {
            $key = mb_strtolower(trim((string) $row->category));
            if (! in_array($key, $official, true)) {
                DB::table('category_bucket_mappings')
                    ->where('id', $row->id)
                    ->update(['is_active' => false, 'updated_at' => now()]);
            }
        }

        foreach ((array) config('category_bucket_mappings_defaults', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            DB::table('category_bucket_mappings')->updateOrInsert(
                [
                    'category' => $row['category'],
                    'sub_category' => $row['sub_category'] ?? null,
                    'transaction_type' => $row['transaction_type'] ?? 'expense',
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                ],
                [
                    'bucket' => $row['bucket'],
                    'nature' => $row['nature'] ?? null,
                    'match_keywords' => $row['match_keywords'] ?? null,
                    'reason' => $row['reason'] ?? 'YFD AI Taxonomy FINAL REVISED',
                    'is_active' => true,
                    'updated_at' => now(),
                ],
            );
        }

        Cache::forget('category_bucket_mappings:active');
    }

    public function down(): void
    {
        // One-way taxonomy sync.
    }
};
