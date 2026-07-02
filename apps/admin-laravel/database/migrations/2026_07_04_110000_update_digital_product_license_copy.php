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

        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'tagline' => 'Catat keuangan harian via chat — lisensi bot & dashboard selamanya.',
            'description' => 'YFD Bot Telegram adalah asisten keuangan pribadi berbasis chat. Tinggal kirim pesan biasa seperti "makan malam 50rb" atau "beli kopi 18000 karena ngantuk", AI YFD otomatis mengekstrak nominal, kategori, jenis transaksi, sifat (Need/Wants/Saving/Donation), mood, dan bahkan menandai pembelian impulsif. Semua tersimpan di dashboard web pribadi. Sekali bayar — akses bot & dashboard berlaku selamanya.',
            'period' => 'selamanya',
            'features' => json_encode([
                'AI parser bahasa alami (Gemini)',
                'Klasifikasi otomatis 7 dimensi finansial',
                'Dashboard web real-time (portal YFD)',
                'Sistem lisensi & akun terisolasi (privat)',
                'Akses bot & dashboard selamanya (sekali bayar)',
                'Onboarding 1×24 jam oleh tim YFD',
                'Akses grup komunitas pengguna',
            ], JSON_UNESCAPED_UNICODE),
            'meta_description' => 'Catat keuangan harian via chat di Telegram. Lisensi bot & dashboard YFD berlaku selamanya — sekali bayar.',
            'updated_at' => now(),
        ]);

        DB::table('cp_digital_products')->where('code', 'yfd-ftsa-premium')->update([
            'tagline' => 'Unlock FTSA 1–32 untuk analisis behavioral — masa aktif 12 bulan evaluasi.',
            'description' => 'Add-on untuk membuka kuesioner FTSA 1–32 di portal YFD. Setelah pembayaran sukses, fitur FTSA aktif selama 12 bulan evaluasi pada akun lisensi Anda. Jika sudah punya bot YFD, upgrade memakai lisensi yang sama.',
            'period' => '12 bulan evaluasi',
            'features' => json_encode([
                'Akses penuh kuesioner FTSA 1–32 di portal',
                'Skoring otomatis CHD, RVD, SSD, ESD',
                'Archetype trauma finansial personal',
                'Insight behavioral lanjutan di dashboard',
                'Masa aktif 12 bulan evaluasi sejak pembayaran',
            ], JSON_UNESCAPED_UNICODE),
            'meta_description' => 'Unlock FTSA 1–32 selama 12 bulan evaluasi. Diagnosis behavioral finansial yang lebih personal di portal YFD.',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Tidak mengembalikan copy lama.
    }
};
