<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $features = [
            'Akses penuh kuesioner FTSA 1–32 di portal',
            'Skoring otomatis CHD, RVD, SSD, ESD',
            'Archetype trauma finansial personal',
            'Insight behavioral lanjutan di dashboard',
            'Unlock permanen untuk lisensi aktif',
        ];

        DB::table('cp_digital_products')->updateOrInsert(
            ['code' => 'yfd-ftsa-premium'],
            [
                'name' => 'FTSA Premium Unlock',
                'tagline' => 'Unlock diagnostik FTSA-32 untuk analisis behavioral yang lebih dalam.',
                'description' => 'Produk add-on untuk membuka akses kuesioner FTSA 1–32 di portal YFD. Setelah pembayaran sukses, fitur FTSA otomatis terbuka pada akun lisensi aktif Anda.',
                'icon' => 'psychology',
                'badge' => 'Tersedia',
                'is_active' => true,
                'is_featured' => false,
                'sort' => 5,
                'price' => 99000,
                'discount_price' => 69000,
                'currency' => 'IDR',
                'period' => 'sekali bayar',
                'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
                'billing_mode' => 'midtrans',
                'cta_label' => 'Unlock FTSA',
                'meta_title' => 'FTSA Premium Unlock — YFD',
                'meta_description' => 'Unlock FTSA 1–32 dan dapatkan diagnosis behavioral finansial yang lebih personal.',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('cp_digital_products')->where('code', 'yfd-ftsa-premium')->delete();
    }
};
