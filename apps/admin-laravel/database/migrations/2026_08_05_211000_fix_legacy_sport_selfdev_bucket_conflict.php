<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix legacy hardcoded mappings that contradict taxonomy v1.3:
 * - Gym/olahraga berbayar → Flexible + Social (bukan Essential Living)
 * - Hapus wildcard self-dev yang menangkap "coaching tenis" sebagai Future Building
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        // Hapus baris legacy dari 2026_07_15_030000 yang kontradiktif.
        DB::table('category_bucket_mappings')
            ->where('category', '*')
            ->where('transaction_type', 'expense')
            ->whereIn('sort_order', [20, 22])
            ->where(function ($q) {
                $q->where('match_keywords', 'like', '%coaching tenis%')
                    ->orWhere('match_keywords', 'like', '%les olahraga%')
                    ->orWhere('reason', 'like', '%self-development selain olahraga%')
                    ->orWhere('reason', 'like', '%Les/coaching olahraga%');
            })
            ->delete();

        // Pastikan olahraga berbayar = Flexible (wildcard, prioritas tinggi).
        DB::table('category_bucket_mappings')->updateOrInsert(
            [
                'category' => '*',
                'sub_category' => '-',
                'transaction_type' => 'expense',
                'sort_order' => 19,
            ],
            [
                'bucket' => 'Flexible + Social',
                'nature' => 'Wants',
                'match_keywords' => 'gym,yoga,pilates,crossfit,personal trainer,coaching tenis,coaching padel,coaching olahraga,les olahraga,les renang,les tenis,kelas pilates,kelas yoga,kelas gym,tenis,renang,padel,fitness coach',
                'reason' => 'Gym/olahraga berbayar selalu Flexible + Social (taxonomy v1.3)',
                'is_active' => true,
                'updated_at' => now(),
            ],
        );

        // Sync ulang defaults resmi.
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
        //
    }
};
