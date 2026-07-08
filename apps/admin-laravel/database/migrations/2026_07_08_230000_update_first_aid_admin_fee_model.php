<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        $now = now();

        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'tagline' => 'Bukan sekedar pencatat keuangan. Ini adalah langkah pertama untuk memahami dan membangun kesehatan finansial Anda.',
            'description' => "Asisten kesehatan finansial berbasis AI.\n\nCatat transaksi cukup lewat chat/kirim foto struk pembelian. AI akan mengubahnya menjadi Financial Health Dashboard, mendeteksi perilaku impulsif, dan membantu Anda memahami kondisi kesehatan finansial.\n\nPembelian mencakup akses bot & dashboard plus biaya admin selama 1 tahun. Setelah itu, lanjutkan dengan biaya admin Rp10.000/bulan atau Rp99.000/tahun.",
            'period' => '1 tahun (termasuk biaya admin)',
            'features' => json_encode([
                'Catat transaksi cukup lewat chat',
                'AI otomatis mengelompokkan pengeluaran/pemasukan',
                'Dashboard langsung ter-update',
                'Deteksi pembelian impulsif',
                'Kenali pola kebiasaan finansial — ketahui bukan hanya ke mana uang Anda pergi, tetapi juga mengapa',
                'Dapatkan insight & rekomendasi',
                'Gratis biaya admin selama 1 tahun pertama',
                'Tahun berikutnya: Rp10.000/bulan atau Rp99.000/tahun',
            ], JSON_UNESCAPED_UNICODE),
            'meta_description' => 'Catat keuangan harian via chat di Telegram. Termasuk biaya admin 1 tahun; setelah itu perpanjang Rp10.000/bulan atau Rp99.000/tahun.',
            'updated_at' => $now,
        ]);

        $renewals = [
            [
                'code' => 'yfd-bot-admin-monthly',
                'name' => 'Biaya Admin First Aid — Bulanan',
                'tagline' => 'Perpanjang akses bot & dashboard — Rp10.000/bulan.',
                'description' => 'Biaya admin bulanan untuk mempertahankan akses YFD First Aid (bot Telegram + dashboard) setelah tahun pertama. Perpanjang 1 bulan setiap pembayaran.',
                'icon' => 'calendar_month',
                'badge' => 'Perpanjang',
                'is_active' => true,
                'is_featured' => false,
                'sort' => 2,
                'price' => 10000,
                'discount_price' => null,
                'currency' => 'IDR',
                'period' => 'per bulan',
                'features' => json_encode([
                    'Perpanjang akses bot & dashboard 1 bulan',
                    'Untuk pengguna First Aid setelah tahun pertama',
                    'Bisa diganti ke paket tahunan kapan saja',
                ], JSON_UNESCAPED_UNICODE),
                'billing_mode' => 'midtrans',
                'cta_label' => 'Bayar Bulanan',
                'meta_title' => 'Biaya Admin First Aid Bulanan — YFD',
                'meta_description' => 'Perpanjang YFD First Aid dengan biaya admin Rp10.000/bulan.',
            ],
            [
                'code' => 'yfd-bot-admin-yearly',
                'name' => 'Biaya Admin First Aid — Tahunan',
                'tagline' => 'Perpanjang akses bot & dashboard — Rp99.000/tahun.',
                'description' => 'Biaya admin tahunan untuk mempertahankan akses YFD First Aid (bot Telegram + dashboard) setelah tahun pertama. Lebih hemat dibanding bayar bulanan.',
                'icon' => 'event_available',
                'badge' => 'Perpanjang',
                'is_active' => true,
                'is_featured' => false,
                'sort' => 3,
                'price' => 99000,
                'discount_price' => null,
                'currency' => 'IDR',
                'period' => 'per tahun',
                'features' => json_encode([
                    'Perpanjang akses bot & dashboard 12 bulan',
                    'Lebih hemat dibanding Rp10.000 × 12',
                    'Untuk pengguna First Aid setelah tahun pertama',
                ], JSON_UNESCAPED_UNICODE),
                'billing_mode' => 'midtrans',
                'cta_label' => 'Bayar Tahunan',
                'meta_title' => 'Biaya Admin First Aid Tahunan — YFD',
                'meta_description' => 'Perpanjang YFD First Aid dengan biaya admin Rp99.000/tahun.',
            ],
        ];

        foreach ($renewals as $row) {
            $existing = DB::table('cp_digital_products')->where('code', $row['code'])->first();
            $payload = array_merge($row, [
                'updated_at' => $now,
            ]);

            if ($existing) {
                DB::table('cp_digital_products')->where('code', $row['code'])->update($payload);
            } else {
                DB::table('cp_digital_products')->insert(array_merge($payload, [
                    'created_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        DB::table('cp_digital_products')->whereIn('code', [
            'yfd-bot-admin-monthly',
            'yfd-bot-admin-yearly',
        ])->delete();
    }
};
