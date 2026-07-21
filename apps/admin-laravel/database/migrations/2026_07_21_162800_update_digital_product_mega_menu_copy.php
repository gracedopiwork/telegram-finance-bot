<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi copy mega-menu Produk Digital (feedback Ayuti — Jul 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'tagline' => 'Bukan sekadar mencatat transaksi. YFD First Aid membantu Anda memahami pola kebiasaan finansial yang memengaruhi kesehatan finansial Anda.',
            'updated_at' => now(),
        ]);

        DB::table('cp_digital_products')->where('code', 'yfd-ftsa-premium')->update([
            'name' => 'FTSA Premium',
            'tagline' => 'Kenali Archetype Keuangan Anda Sebelum Mengatur Uang',
            'is_featured' => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'tagline' => 'Bukan sekedar pencatat keuangan. Ini adalah langkah pertama untuk memahami dan membangun kesehatan finansial Anda.',
            'updated_at' => now(),
        ]);

        DB::table('cp_digital_products')->where('code', 'yfd-ftsa-premium')->update([
            'name' => 'FTSA Premium Unlock',
            'tagline' => 'Unlock FTSA 1–32 untuk analisis behavioral — masa aktif 12 bulan evaluasi.',
            'is_featured' => false,
            'updated_at' => now(),
        ]);
    }
};
