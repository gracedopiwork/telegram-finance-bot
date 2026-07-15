<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
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
        // Mapping dapat disesuaikan admin setelah deploy; rollback tidak menimpa pilihan admin.
    }
};
