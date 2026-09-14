<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Education Platform CTAs + Produk Edukasi cards (brief Catherina).
 * CTA: Daftar Workshop + Kerjasama dengan YFD (bukan Wealthpedia).
 * Cards: Webinar tetap, Workshop baru, E-book tetap, Jurnal coming soon.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $updates = [
            'bundle.education.cta_primary_label' => 'Daftar Workshop',
            'bundle.education.cta_secondary_label' => 'Kerjasama dengan YFD',
            'bundle.education.footnote' => 'Untuk info jadwal webinar, workshop, dan harga terbaru, hubungi tim YFD.',
        ];

        foreach ($updates as $key => $value) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => str_contains($key, 'footnote') ? 'textarea' : 'text',
                    'group' => 'page_bundles',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
