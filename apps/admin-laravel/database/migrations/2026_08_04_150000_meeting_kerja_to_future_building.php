<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ekspektasi klien: konsumsi meeting kerja → Future Building (bukan Essential Living).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        // Nonaktifkan mapping lama: Makanan + meeting kerja → Essential.
        DB::table('category_bucket_mappings')
            ->where('category', 'Makanan & Minuman')
            ->where('transaction_type', 'expense')
            ->where('bucket', 'Essential Living')
            ->where('sort_order', 23)
            ->update(['is_active' => false, 'updated_at' => now()]);

        foreach ((array) config('category_bucket_mappings_defaults', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $sort = (int) ($row['sort_order'] ?? $index);
            if (! in_array($sort, [20, 23, 25, 26, 29], true)) {
                continue;
            }

            DB::table('category_bucket_mappings')->updateOrInsert(
                [
                    'category' => $row['category'],
                    'sub_category' => $row['sub_category'] ?? null,
                    'transaction_type' => $row['transaction_type'] ?? 'expense',
                    'sort_order' => $sort,
                ],
                [
                    'bucket' => $row['bucket'],
                    'nature' => $row['nature'] ?? null,
                    'match_keywords' => $row['match_keywords'] ?? null,
                    'reason' => $row['reason'] ?? 'Meeting kerja → Future Building',
                    'is_active' => true,
                    'updated_at' => now(),
                ],
            );
        }

        Cache::forget('category_bucket_mappings:active');
    }

    public function down(): void
    {
        // One-way client expectation sync.
    }
};
