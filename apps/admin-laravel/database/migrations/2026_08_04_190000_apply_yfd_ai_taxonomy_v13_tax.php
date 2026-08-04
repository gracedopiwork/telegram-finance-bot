<?php

use App\Support\TransactionTaxonomy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * YFD AI Taxonomy v1.3 (2 Agustus 2026):
 * - Kategori 17: Biaya Legal, Administrasi & Peristiwa Besar
 * - Jenis baru: Kewajiban Pajak (di luar 4 bucket)
 * - Resync bucket mappings (PBB, pajak kendaraan, notaris, dll.)
 * - Setting copy panel Kesehatan Pajak (referral tax planner)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_transactions') && DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM('Pemasukan', 'Pengeluaran', 'Saving/Investment', 'Kewajiban Pajak') NOT NULL"
            );
        }

        if (Schema::hasTable('settings')) {
            $now = now();
            $rows = [
                [
                    'key' => 'portal.tax_health_title',
                    'value' => 'Kesehatan Pajak',
                    'type' => 'text',
                    'group' => 'bot',
                    'label' => 'Portal — Judul panel Kesehatan Pajak',
                    'sort' => 80,
                ],
                [
                    'key' => 'portal.tax_health_body',
                    'value' => 'Pantau kewajiban pajak (PPh 25/29) terpisah dari 4 bucket. Estimasi pajak bukan angka final — diskusikan dengan tax planner YFD agar cadangan dan pelaporan tetap sehat.',
                    'type' => 'textarea',
                    'group' => 'bot',
                    'label' => 'Portal — Teks panel Kesehatan Pajak',
                    'sort' => 81,
                ],
                [
                    'key' => 'portal.tax_health_cta',
                    'value' => 'Konsultasi Tax Planner',
                    'type' => 'text',
                    'group' => 'bot',
                    'label' => 'Portal — Label CTA Kesehatan Pajak',
                    'sort' => 82,
                ],
                [
                    'key' => 'portal.tax_health_wa_message',
                    'value' => 'Halo YFD, saya ingin konsultasi kesehatan pajak / tax planner (PPh 25/29 & perencanaan pajak).',
                    'type' => 'textarea',
                    'group' => 'bot',
                    'label' => 'Portal — Pesan WA referral tax planner',
                    'sort' => 83,
                ],
            ];

            foreach ($rows as $row) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $row['key']],
                    array_merge($row, ['created_at' => $now, 'updated_at' => $now]),
                );
            }
        }

        if (! Schema::hasTable('category_bucket_mappings')) {
            Cache::forget('settings.all');
            Cache::forget('category_bucket_mappings:active');

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
                    'created_at' => now(),
                ],
            );
        }

        // Reclassify historical PPh-like notes that were parked in Lain-lain.
        if (Schema::hasTable('bot_transactions')) {
            DB::table('bot_transactions')
                ->where('type', TransactionTaxonomy::TYPE_EXPENSE)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(notes) LIKE ?', ['%pph 25%'])
                        ->orWhereRaw('LOWER(notes) LIKE ?', ['%pph25%'])
                        ->orWhereRaw('LOWER(notes) LIKE ?', ['%pph 29%'])
                        ->orWhereRaw('LOWER(notes) LIKE ?', ['%pph29%'])
                        ->orWhereRaw('LOWER(notes) LIKE ?', ['%angsuran pajak%'])
                        ->orWhereRaw('LOWER(notes) LIKE ?', ['%kurang bayar pajak%']);
                })
                ->update([
                    'type' => TransactionTaxonomy::TYPE_TAX,
                    'updated_at' => now(),
                ]);
        }

        Cache::forget('settings.all');
        Cache::forget('category_bucket_mappings:active');
        Cache::forget('bot.category_rules');
    }

    public function down(): void
    {
        if (Schema::hasTable('bot_transactions')) {
            DB::table('bot_transactions')
                ->where('type', TransactionTaxonomy::TYPE_TAX)
                ->update(['type' => TransactionTaxonomy::TYPE_EXPENSE]);

            if (DB::getDriverName() === 'mysql') {
                DB::statement(
                    "ALTER TABLE bot_transactions MODIFY COLUMN type ENUM('Pemasukan', 'Pengeluaran', 'Saving/Investment') NOT NULL"
                );
            }
        }
    }
};
