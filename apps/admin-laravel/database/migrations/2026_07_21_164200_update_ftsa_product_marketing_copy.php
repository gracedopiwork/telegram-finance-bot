<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copy marketing FTSA lengkap (feedback Ayuti — Jul 2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        $description = <<<'TXT'
Dua orang bisa memiliki penghasilan yang sama, tetapi mengambil keputusan finansial yang sangat berbeda. Mengapa?

Karena setiap orang memiliki archetype atau pola kepribadian finansial yang terbentuk dari pengalaman hidup, cara berpikir, dan regulasi diri. FTSA (Financial Trauma Self Assessment) membantu Anda mengenali pola tersebut sehingga Anda dapat memahami mengapa Anda mengambil keputusan finansial tertentu—bukan hanya apa yang harus dilakukan.
TXT;

        $features = json_encode([
            'Hasil Financial Personality Archetype',
            'Pemetaan kecenderungan regulasi diri finansial',
            'Skor pada setiap dimensi FTSA',
            'Penjelasan pola perilaku yang paling dominan',
            'Area yang berpotensi menghambat Financial Health',
            'Rekomendasi langkah awal yang sesuai dengan hasil Anda',
        ], JSON_UNESCAPED_UNICODE);

        DB::table('cp_digital_products')->where('code', 'yfd-ftsa-premium')->update([
            'name' => 'FTSA',
            'tagline' => 'Kenali Archetype Keuangan Anda Sebelum Mengatur Uang',
            'description' => $description,
            'features' => $features,
            'is_featured' => true,
            'meta_title' => 'FTSA — Financial Trauma Self Assessment | YFD',
            'meta_description' => 'Kenali archetype keuangan dan pola perilaku finansial Anda dengan FTSA 1–32. Pahami mengapa Anda mengambil keputusan finansial tertentu.',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        DB::table('cp_digital_products')->where('code', 'yfd-ftsa-premium')->update([
            'name' => 'FTSA Premium',
            'tagline' => 'Kenali diri sebelum mengatur uang. FTSA membantu Anda memahami pola perilaku dan faktor emosional yang memengaruhi keputusan finansial.',
            'description' => 'Add-on untuk membuka dashboard FTSA di portal YFD: kuesioner FTSA 1–32, behavioral insight, dan indeks kesehatan finansial selama 12 bulan evaluasi.',
            'features' => json_encode([
                'Dashboard FTSA & behavioral di portal web',
                'Kuesioner FTSA 1–32 lengkap',
                'Skoring otomatis CHD, RVD, SSD, ESD',
                'Archetype trauma finansial personal',
                'Masa aktif 12 bulan evaluasi sejak pembayaran',
            ], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }
};
