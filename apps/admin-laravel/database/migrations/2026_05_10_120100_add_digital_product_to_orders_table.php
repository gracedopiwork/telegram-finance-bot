<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Foreign key ke produk digital. Nullable supaya order legacy tetap valid.
            $table->foreignId('digital_product_id')
                  ->nullable()
                  ->after('plan')
                  ->constrained('cp_digital_products')
                  ->nullOnDelete();

            $table->string('product_name', 150)->nullable()->after('digital_product_id');

            // Diskon snapshot saat order dibuat
            $table->unsignedBigInteger('original_price')->nullable()->after('amount');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('original_price');

            // Customer phone (opsional, biasanya WA contact)
            $table->string('phone', 32)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['digital_product_id']);
            $table->dropColumn([
                'digital_product_id',
                'product_name',
                'original_price',
                'discount_amount',
                'phone',
            ]);
        });
    }
};
