<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kartu konsultasi 1-on-1 di /layanan → halaman detail /layanan/konsultasi (bukan langsung booking).
 * Prod: tepat satu baris dengan cta_route 'company.pertemuan' (id=3, dicek 2026-10-25).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cp_services')) {
            return;
        }

        DB::table('cp_services')
            ->where('cta_route', 'company.pertemuan')
            ->update(['cta_route' => 'company.konsultasi', 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('cp_services')) {
            return;
        }

        DB::table('cp_services')
            ->where('cta_route', 'company.konsultasi')
            ->update(['cta_route' => 'company.pertemuan', 'updated_at' => now()]);
    }
};
