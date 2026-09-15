<?php

use App\Models\CpService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Education Platform: lepas Wealthpedia (punya section sendiri),
 * deskripsi + cakupan sesuai brief Catherina.
 */
return new class extends Migration
{
    public function up(): void
    {
        $description = 'Pusat edukasi kesehatan Finansial YFD — webinar, workshop, komunitas belajar.';
        $features = implode("\n", [
            'Behavioral Finance Education',
            'Webinar & Workshop',
            'Kelas Online',
            'Research & Journal',
            'E-book & Practical Guide',
            'Community Learning',
        ]);

        if (Schema::hasTable('site_settings')) {
            $updates = [
                'bundle.education.description' => $description,
                'bundle.education.features' => $features,
                'bundle.education.footnote' => 'Untuk info jadwal webinar, workshop, kelas, dan harga terbaru, hubungi tim YFD.',
                'bundle.education.cta_primary_label' => 'Daftar Workshop',
                'bundle.education.cta_secondary_label' => 'Kerjasama dengan YFD',
            ];
            foreach ($updates as $key => $value) {
                DB::table('site_settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => str_contains($key, 'features') || str_contains($key, 'footnote') || str_contains($key, 'description')
                            ? 'textarea'
                            : 'text',
                        'group' => 'page_bundles',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('cp_services')) {
            CpService::query()
                ->where('title', 'Financial Education Platform')
                ->update([
                    'description' => $description,
                    'features' => [
                        'label' => 'Cakupan',
                        'items' => [
                            'Behavioral Finance Education',
                            'Webinar & Workshop',
                            'Kelas Online',
                            'Research & Journal',
                            'E-book & Practical Guide',
                            'Community Learning',
                        ],
                    ],
                    'cta_label' => 'Daftar Workshop',
                    'cta_route' => 'company.bundle.education',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Tidak rollback copy konten — biarkan nilai baru.
    }
};
