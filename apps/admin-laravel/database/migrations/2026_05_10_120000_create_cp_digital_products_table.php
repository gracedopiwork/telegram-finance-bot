<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cp_digital_products', function (Blueprint $table) {
            $table->id();

            // Identitas
            $table->string('code', 50)->unique();         // slug-like, untuk URL & order
            $table->string('name', 150);
            $table->string('tagline', 200)->nullable();   // tagline pendek di card
            $table->text('description')->nullable();      // deskripsi panjang
            $table->string('icon', 60)->default('auto_awesome'); // material symbol
            $table->string('image_url')->nullable();

            // Status
            $table->string('badge', 60)->nullable();      // "Tersedia", "Coming Soon", "Beta"
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);

            // Harga
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('discount_price')->nullable();
            $table->string('currency', 5)->default('IDR');
            $table->string('period', 60)->default('per tahun'); // "per bulan", "lifetime", "sekali bayar"

            // Fitur (list bullet)
            $table->json('features')->nullable();

            // Mode pembelian
            // 'midtrans' = checkout via Midtrans
            // 'wa'       = arahkan ke WhatsApp tanpa transaksi otomatis
            // 'url'      = link eksternal (App Store / Play Store / dst.)
            // 'soon'     = tampilkan tapi belum bisa dibeli (Coming Soon)
            $table->enum('billing_mode', ['midtrans', 'wa', 'url', 'soon'])->default('midtrans');
            $table->string('cta_label', 80)->default('Beli Sekarang');
            $table->string('cta_url')->nullable();        // dipakai jika mode='url'

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'sort']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cp_digital_products');
    }
};
