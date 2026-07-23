<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sinkron ke YFD AI Taxonomy v1.0 — closed list 15 kategori.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'makan' => 'Makanan & Minuman',
            'jajan' => 'Makanan & Minuman',
            'makanan' => 'Makanan & Minuman',
            'transport' => 'Transportasi',
            'transportasi' => 'Transportasi',
            'listrik' => 'Tempat Tinggal',
            'air' => 'Tempat Tinggal',
            'sewa/tempat tinggal' => 'Tempat Tinggal',
            'laundry' => 'Tempat Tinggal',
            'kesehatan' => 'Kesehatan & Kebersihan Diri',
            'skincare' => 'Lifestyle & Hiburan',
            'hiburan' => 'Lifestyle & Hiburan',
            'subscription' => 'Lifestyle & Hiburan',
            'elektronik' => 'Lifestyle & Hiburan',
            'peralatan' => 'Lifestyle & Hiburan',
            'asuransi' => 'Proteksi',
            'social' => 'Sosial & Keluarga',
            'sosial' => 'Sosial & Keluarga',
            'cicilan' => 'Cicilan & Hutang',
            'pajak' => 'Lain-lain',
            'jasa' => 'Bisnis & Karir',
            'saham' => 'Investasi & Tabungan',
            'reksadana' => 'Investasi & Tabungan',
            'emas' => 'Investasi & Tabungan',
            'obligasi' => 'Investasi & Tabungan',
            'lainnya' => 'Lain-lain',
        ];

        if (Schema::hasTable('bot_transactions')) {
            foreach ($map as $from => $to) {
                DB::table('bot_transactions')
                    ->whereRaw('LOWER(TRIM(category)) = ?', [$from])
                    ->update(['category' => $to]);
            }
        }

        if (Schema::hasTable('category_bucket_mappings')) {
            foreach ($map as $from => $to) {
                DB::table('category_bucket_mappings')
                    ->whereRaw('LOWER(TRIM(category)) = ?', [$from])
                    ->update(['category' => $to]);
            }

            // Seed missing official categories from defaults if absent.
            $defaults = (array) config('category_bucket_mappings_defaults', []);
            $existing = DB::table('category_bucket_mappings')
                ->pluck('category')
                ->map(fn ($c) => mb_strtolower(trim((string) $c)))
                ->all();

            $maxSort = (int) DB::table('category_bucket_mappings')->max('sort_order');
            foreach ($defaults as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $cat = (string) ($row['category'] ?? '');
                if ($cat === '' || $cat === '*') {
                    continue;
                }
                if (in_array(mb_strtolower($cat), $existing, true)) {
                    continue;
                }
                $maxSort++;
                DB::table('category_bucket_mappings')->insert([
                    'category' => $cat,
                    'sub_category' => $row['sub_category'] ?? '-',
                    'bucket' => $row['bucket'] ?? 'Flexible + Social',
                    'transaction_type' => $row['transaction_type'] ?? 'expense',
                    'nature' => $row['nature'] ?? null,
                    'match_keywords' => $row['match_keywords'] ?? null,
                    'reason' => $row['reason'] ?? 'YFD AI Taxonomy v1.0',
                    'sort_order' => $row['sort_order'] ?? $maxSort,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $existing[] = mb_strtolower($cat);
            }
        }
    }

    public function down(): void
    {
        // One-way taxonomy migration.
    }
};
