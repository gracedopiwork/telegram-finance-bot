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

        DB::table('cp_digital_products')->where('code', 'yfd-first-aid-ftsa')->update([
            'price' => 349000,
            'discount_price' => 229000,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        DB::table('cp_digital_products')->where('code', 'yfd-first-aid-ftsa')->update([
            'price' => 249000,
            'discount_price' => 229000,
            'updated_at' => now(),
        ]);
    }
};
