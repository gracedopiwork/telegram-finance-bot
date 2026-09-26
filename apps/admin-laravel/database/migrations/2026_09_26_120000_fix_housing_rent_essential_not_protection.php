<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sewa kos / listrik / utilitas tinggal = Essential Living, bukan Protection.
 * Bersihkan mapping Proteksi yang nyasar keyword rumah tangga sewa/utilitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        // Pastikan mapping resmi Tempat Tinggal (sort 31) aktif.
        foreach ((array) config('category_bucket_mappings_defaults', []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $sort = (int) ($row['sort_order'] ?? $index);
            if ($sort !== 31) {
                continue;
            }
            DB::table('category_bucket_mappings')->updateOrInsert(
                [
                    'category' => $row['category'],
                    'sub_category' => $row['sub_category'] ?? '-',
                    'transaction_type' => $row['transaction_type'] ?? 'expense',
                    'sort_order' => $sort,
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

        // Hapus mapping Tempat Tinggal → Protection (salah konfigurasi admin/DB).
        DB::table('category_bucket_mappings')
            ->whereRaw('LOWER(TRIM(category)) = ?', ['tempat tinggal'])
            ->where('bucket', 'Protection')
            ->where('transaction_type', 'expense')
            ->delete();

        $pollute = [
            'kos', 'kost', 'kontrakan', 'listrik', 'pln', 'pdam', 'ipl',
            'wifi rumah', 'sewa', 'sewa kos', 'sewa kost', 'uang kos', 'bayar kos',
            'token listrik', 'sewa rumah', 'bayar sewa', 'uang sewa', 'tagihan air',
        ];
        $rows = DB::table('category_bucket_mappings')
            ->where('bucket', 'Protection')
            ->where('transaction_type', 'expense')
            ->get();
        foreach ($rows as $row) {
            $keywords = array_filter(array_map('trim', preg_split('/[\n,;]+/', (string) $row->match_keywords) ?: []));
            $clean = array_values(array_filter(
                $keywords,
                fn (string $kw) => ! in_array(mb_strtolower($kw), $pollute, true),
            ));
            if ($clean !== array_values($keywords)) {
                DB::table('category_bucket_mappings')->where('id', $row->id)->update([
                    'match_keywords' => implode(',', $clean),
                    'updated_at' => now(),
                ]);
            }
        }

        Cache::forget('category_bucket_mappings:active');
    }

    public function down(): void
    {
        // Irreversible data cleanup — no-op.
    }
};
