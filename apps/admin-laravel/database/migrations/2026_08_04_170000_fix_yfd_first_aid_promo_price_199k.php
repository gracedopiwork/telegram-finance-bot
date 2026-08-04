<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * YFD First Aid: harga promo 199.000 (~33,4% dari 299.000), bukan 149.000 (50%).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('cp_digital_products')->where('code', 'yfd-bot-telegram')->update([
            'price' => 299000,
            'discount_price' => 199000,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Tidak mengembalikan harga salah sebelumnya.
    }
};
