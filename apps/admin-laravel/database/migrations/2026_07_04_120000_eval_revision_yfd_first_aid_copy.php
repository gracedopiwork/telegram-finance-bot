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

        $features = json_encode([
            'Catat transaksi cukup lewat chat',
            'AI otomatis mengelompokkan pengeluaran/pemasukan',
            'Dashboard langsung ter-update',
            'Deteksi pembelian impulsif',
            'Kenali pola kebiasaan finansial — ketahui bukan hanya ke mana uang Anda pergi, tetapi juga mengapa',
            'Dapatkan insight & rekomendasi',
        ], JSON_UNESCAPED_UNICODE);

        $payload = [
            'name' => 'YFD First Aid',
            'tagline' => 'Bukan sekedar pencatat keuangan. Ini adalah langkah pertama untuk memahami dan membangun kesehatan finansial Anda.',
            'description' => "Asisten kesehatan finansial berbasis AI.\n\nCatat transaksi cukup lewat chat/kirim foto struk pembelian. AI akan mengubahnya menjadi Financial Health Dashboard, mendeteksi perilaku impulsif, dan membantu Anda memahami kondisi kesehatan finansial.\n\nSekali bayar. Akses bot & dashboard selamanya.",
            'is_active' => true,
            'is_featured' => true,
            'billing_mode' => 'midtrans',
            'price' => 299000,
            'discount_price' => 199000,
            'period' => 'selamanya',
            'features' => $features,
            'cta_label' => 'Beli Sekarang',
            'badge' => 'Tersedia',
            'meta_title' => 'YFD First Aid — Asisten Kesehatan Finansial AI',
            'meta_description' => 'Catat transaksi via chat Telegram. AI parsing otomatis, deteksi impulsif, dashboard keuangan — sekali bayar, akses selamanya.',
            'updated_at' => now(),
        ];

        $exists = DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->exists();
        if ($exists) {
            DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update($payload);
        } else {
            DB::table('cp_digital_products')->insert(array_merge($payload, [
                'code' => 'yfd-bot-telegram',
                'icon' => 'send',
                'sort' => 1,
                'currency' => 'IDR',
                'created_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan copy lama.
    }
};
