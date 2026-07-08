<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('cp_services')) {
            return;
        }

        DB::table('cp_services')
            ->where('title', 'Financial Education Platform')
            ->update([
                'cta_label' => 'Lihat Platform Edukasi',
                'cta_route' => 'company.bundle.education',
                'updated_at' => now(),
            ]);

        DB::table('cp_services')
            ->where('title', 'Financial Recovery Program')
            ->update([
                'cta_label' => 'Lihat Program Recovery',
                'cta_route' => 'company.bundle.recovery',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('cp_services')) {
            return;
        }

        DB::table('cp_services')
            ->where('title', 'Financial Education Platform')
            ->update(['cta_route' => 'company.wealthpedia', 'cta_label' => 'Kunjungi Wealthpedia', 'updated_at' => now()]);

        DB::table('cp_services')
            ->where('title', 'Financial Recovery Program')
            ->update(['cta_route' => 'company.pertemuan', 'cta_label' => 'Lihat Paket Konsultasi Recovery', 'updated_at' => now()]);
    }
};
