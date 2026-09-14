<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable Produk Edukasi cards + clearer Education Platform labels.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $now = now();
        $offerings = implode("\n", [
            'Webinar & Seminar|available|cast_for_education|Sesi live dengan dokter finansial & praktisi — topik cashflow, utang, investasi pemula, dll.',
            'Workshop|available|groups|Workshop khusus sesuai kebutuhan komunitas — misalnya Pre-marital, Employee, dan topik komunitas lainnya.',
            'E-book & Practical Guide|soon|menu_book|Panduan praktis kesehatan finansial dalam format digital.',
            'Jurnal|soon|auto_stories|Jurnal edukasi kesehatan finansial YFD.',
        ]);

        $rows = [
            ['key' => 'bundle.education.title', 'value' => 'Financial Education Platform', 'type' => 'text', 'label' => 'Education Platform — Judul', 'sort' => 2],
            ['key' => 'bundle.education.description', 'value' => 'Pusat edukasi kesehatan Finansial YFD — webinar, workshop, komunitas belajar.', 'type' => 'textarea', 'label' => 'Education Platform — Deskripsi', 'sort' => 3],
            ['key' => 'bundle.education.features', 'value' => "Behavioral Finance Education\nWebinar & Workshop\nKelas Online\nResearch & Journal\nE-book & Practical Guide\nCommunity Learning", 'type' => 'textarea', 'label' => 'Education Platform — Cakupan (1 baris = 1 item)', 'sort' => 5],
            ['key' => 'bundle.education.footnote', 'value' => 'Untuk info jadwal webinar, workshop, dan harga terbaru, hubungi tim YFD.', 'type' => 'textarea', 'label' => 'Education Platform — Catatan kaki', 'sort' => 6],
            ['key' => 'bundle.education.cta_primary_label', 'value' => 'Daftar Workshop', 'type' => 'text', 'label' => 'Education Platform — CTA utama', 'sort' => 7],
            ['key' => 'bundle.education.cta_secondary_label', 'value' => 'Kerjasama dengan YFD', 'type' => 'text', 'label' => 'Education Platform — CTA sekunder', 'sort' => 8],
            ['key' => 'bundle.education.offerings', 'value' => $offerings, 'type' => 'textarea', 'label' => 'Education Platform — Produk Edukasi (Judul|status|icon|deskripsi; status=available/soon/free)', 'sort' => 15],
        ];

        foreach ($rows as $row) {
            $payload = [
                'value' => $row['value'],
                'type' => $row['type'],
                'group' => 'page_bundles',
                'label' => $row['label'],
                'sort' => $row['sort'],
                'updated_at' => $now,
            ];

            $exists = DB::table('site_settings')->where('key', $row['key'])->exists();
            if ($exists) {
                // Update label/sort always; refresh offerings content; keep other values if already customized.
                $update = [
                    'type' => $row['type'],
                    'group' => 'page_bundles',
                    'label' => $row['label'],
                    'sort' => $row['sort'],
                    'updated_at' => $now,
                ];
                if ($row['key'] === 'bundle.education.offerings') {
                    $update['value'] = $row['value'];
                }
                DB::table('site_settings')->where('key', $row['key'])->update($update);
            } else {
                DB::table('site_settings')->insert($payload + [
                    'key' => $row['key'],
                    'created_at' => $now,
                ]);
            }
        }

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.page_bundles');
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }
        DB::table('site_settings')->where('key', 'bundle.education.offerings')->delete();
        Cache::forget('site_settings.all');
        Cache::forget('settings.group.page_bundles');
    }
};
