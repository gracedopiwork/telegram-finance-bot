<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cp_partners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('icon', 80)->default('handshake');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('cp_partners')->insert([
            [
                'title' => 'Insurance partner',
                'icon' => 'health_and_safety',
                'description' => 'Mitra asuransi untuk perlindungan jiwa, kesehatan, dan aset sesuai kebutuhan finansial Anda.',
                'sort' => 10,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Manager Investasi',
                'icon' => 'trending_up',
                'description' => 'Pendamping investasi dan pengelolaan portofolio untuk pertumbuhan aset jangka menengah–panjang.',
                'sort' => 20,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Tax analyst',
                'icon' => 'receipt_long',
                'description' => 'Analisis dan perencanaan perpajakan agar keputusan finansial tetap efisien dan patuh regulasi.',
                'sort' => 30,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Wedding organizer',
                'icon' => 'favorite',
                'description' => 'Perencanaan pernikahan yang terukur agar momen spesial tidak mengganggu kesehatan finansial.',
                'sort' => 40,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Property agency',
                'icon' => 'home_work',
                'description' => 'Pendamping keputusan properti — sewa, beli, atau investasi — sesuai kapasitas finansial.',
                'sort' => 50,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Legal / Notaris',
                'icon' => 'gavel',
                'description' => 'Pendampingan legal dan notaris untuk dokumen, perjanjian, serta pengamanan aset sesuai kebutuhan finansial Anda.',
                'sort' => 60,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cp_partners');
    }
};
