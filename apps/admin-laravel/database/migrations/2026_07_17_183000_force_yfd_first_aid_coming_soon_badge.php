<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pastikan YFD First Aid di mega-menu & halaman produk tampil Coming Soon
 * (bukan TERSEDIA), termasuk jika data sempat diubah kembali di admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'billing_mode' => 'soon',
            'badge' => 'Coming Soon',
            'cta_label' => 'Coming Soon',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'billing_mode' => 'midtrans',
            'badge' => 'Tersedia',
            'cta_label' => 'Beli Sekarang',
            'updated_at' => now(),
        ]);
    }
};
