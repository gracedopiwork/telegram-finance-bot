<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sync YFD AI Taxonomy v1.3 grey-area & meeting → Future Building mappings.
 * ART/babysitter, PBB investasi, fisioterapi, pinjol Need/Wants, qurban, aliases.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        // Nonaktifkan legacy: Makanan + meeting → Essential Living (jika masih ada).
        DB::table('category_bucket_mappings')
            ->where('category', 'Makanan & Minuman')
            ->where('transaction_type', 'expense')
            ->where('bucket', 'Essential Living')
            ->where(function ($q) {
                $q->where('match_keywords', 'like', '%meeting%')
                    ->orWhere('match_keywords', 'like', '%starbucks%')
                    ->orWhere('reason', 'like', '%meeting%');
            })
            ->update(['is_active' => false, 'updated_at' => now()]);

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
                    'reason' => $row['reason'] ?? null,
                    'is_active' => true,
                    'updated_at' => now(),
                ],
            );
        }

        Cache::forget('category_bucket_mappings:active');
    }

    public function down(): void
    {
        // Mapping boleh disesuaikan admin setelah deploy.
    }
};
