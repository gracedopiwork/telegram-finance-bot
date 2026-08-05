<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas keyword transport tujuan bisnis → Future Building.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        $keywords = 'pesawat meeting,tiket meeting,training kerja,urusan bisnis,meeting klien,meeting kerja,meeting kerjaan,rapat kerja,perjalanan dinas,networking bisnis,networking,ketemu client,ketemu klien,ketemu bisnis,meeting client,meeting bisnis,klien bisnis,client bisnis,keperluan bisnis,perjalanan bisnis,kerja training,untuk bisnis,tujuan bisnis,ke bisnis,buat bisnis,keperluan usaha,ke klien,ke client,ke meeting,ke rapat,rapat klien,rapat client,ketemu calon klien,pitch client,pitch klien,investor meeting,dinas luar,acara bisnis,event bisnis';

        DB::table('category_bucket_mappings')
            ->where('category', 'Transportasi')
            ->where('bucket', 'Future Building')
            ->where('transaction_type', 'expense')
            ->update([
                'match_keywords' => $keywords,
                'reason' => 'Transportasi bisnis/kerja (lokal & jarak jauh) → Future Building',
                'updated_at' => now(),
            ]);

        if (function_exists('cache')) {
            cache()->forget('category_bucket_mappings:active');
        }
    }

    public function down(): void
    {
        // no-op — keywords expansion is additive
    }
};
