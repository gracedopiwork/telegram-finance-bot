<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feedback Ayuti Jul 2026: gym → Kesehatan; konsumsi meeting bisnis → Future Building.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        DB::table('category_bucket_mappings')
            ->where('category', 'Kesehatan')
            ->where('transaction_type', 'expense')
            ->update([
                'match_keywords' => 'medical check up,obat,apotek,klinik,dokter,rumah sakit,laboratorium,vitamin,gym,olahraga,personal training,personal trainer,fitness,kebugaran,membership gym,laundry,cuci baju,cuci kiloan,dry clean,dryclean',
                'updated_at' => now(),
            ]);

        $exists = DB::table('category_bucket_mappings')
            ->where('match_keywords', 'like', '%konsumsi meeting%')
            ->where('bucket', 'Future Building')
            ->exists();

        if (! $exists) {
            DB::table('category_bucket_mappings')->insert([
                'category' => '*',
                'sub_category' => '-',
                'bucket' => 'Future Building',
                'transaction_type' => 'expense',
                'nature' => 'Need',
                'match_keywords' => 'konsumsi meeting,take konten,konten bisnis,meeting bisnis,rapat bisnis,bisnis yfd',
                'reason' => 'Konsumsi untuk pertemuan bisnis / produksi konten',
                'sort_order' => 21,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $socialExists = DB::table('category_bucket_mappings')
            ->where('category', 'Social')
            ->where('bucket', 'Flexible + Social')
            ->where('match_keywords', 'like', '%hadiah%')
            ->exists();

        if (! $socialExists) {
            DB::table('category_bucket_mappings')->insert([
                'category' => 'Social',
                'sub_category' => '-',
                'bucket' => 'Flexible + Social',
                'transaction_type' => 'expense',
                'nature' => 'Wants',
                'match_keywords' => 'hadiah,tip,tips,donasi,buat tips,uang rokok,uang terima kasih',
                'reason' => 'Pemberian sosial diskresioner',
                'sort_order' => 22,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        DB::table('category_bucket_mappings')
            ->where('match_keywords', 'like', '%konsumsi meeting%')
            ->where('bucket', 'Future Building')
            ->delete();

        DB::table('category_bucket_mappings')
            ->where('category', 'Social')
            ->where('match_keywords', 'like', '%hadiah%')
            ->where('bucket', 'Flexible + Social')
            ->delete();
    }
};
