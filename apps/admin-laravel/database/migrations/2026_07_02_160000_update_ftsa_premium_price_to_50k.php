<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cp_digital_products')
            ->where('code', 'yfd-ftsa-premium')
            ->update([
                'price' => 50000,
                'discount_price' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('cp_digital_products')
            ->where('code', 'yfd-ftsa-premium')
            ->update([
                'price' => 99000,
                'discount_price' => 69000,
                'updated_at' => now(),
            ]);
    }
};
