<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Premarital Financial Health Check Up sebagai layanan #4.
 * Education → 5, Recovery → 6, Human Capital → 7 (total 7 layanan).
 *
 * @see docs/YFD_Premarital_Copy_Draft.md.pdf
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cp_services')) {
            return;
        }

        $now = now();

        // Geser urutan layanan lama (kerja dari belakang agar tidak bentrok).
        DB::table('cp_services')
            ->where('title', 'like', 'Education Financing%')
            ->update(['sort' => 7, 'updated_at' => $now]);

        DB::table('cp_services')
            ->where('title', 'Financial Recovery Program')
            ->update(['sort' => 6, 'updated_at' => $now]);

        DB::table('cp_services')
            ->where('title', 'Financial Education Platform')
            ->update([
                'sort' => 5,
                'cta_route' => 'company.bundle.education',
                'updated_at' => $now,
            ]);

        $features = json_encode([
            'label' => 'Cakupan',
            'items' => [
                'Analisis kondisi finansial masing-masing individu (male & female terpisah)',
                'Diskusi keselarasan tujuan & nilai finansial pasangan',
                'Identifikasi red flags finansial sebelum menikah (utang tersembunyi, gaya hidup, kewajiban finansial keluarga)',
                'Financial Medical Report (FMR) untuk pasangan',
                'Rencana finansial bersama pasca-menikah',
                'Sesi follow-up evaluasi kesepakatan',
            ],
            'footnote' => "Sesi 1-on-1 (male & female): tarif mengikuti tahap finansial masing-masing — lihat tabel tarif konsultasi individu.\nSesi couple (setelah kedua sesi individu selesai): Rp 500.000 (termasuk 1× follow-up).\nKedua sesi individu wajib dengan dokter yang sama.",
        ], JSON_UNESCAPED_UNICODE);

        $exists = DB::table('cp_services')
            ->where('title', 'Premarital Financial Health Check Up')
            ->exists();

        $payload = [
            'section' => 'main',
            'eyebrow' => 'Premarital',
            'title' => 'Premarital Financial Health Check Up',
            'description' => 'Kesehatan finansial pasangan adalah pondasi rumah tangga — diperiksa sebelum menikah, bukan disesali sesudahnya.',
            'icon' => 'diversity_1',
            'features' => $features,
            'cta_label' => 'Lihat Premarital Plan',
            'cta_route' => 'company.bundle.premarital',
            'sort' => 4,
            'is_active' => true,
            'updated_at' => $now,
        ];

        if ($exists) {
            DB::table('cp_services')
                ->where('title', 'Premarital Financial Health Check Up')
                ->update($payload);
        } else {
            DB::table('cp_services')->insert(array_merge($payload, [
                'image_path' => null,
                'created_at' => $now,
            ]));
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'home.services_title'],
                [
                    'value' => 'Tujuh Layanan Kesehatan Finansial',
                    'type' => 'text',
                    'group' => 'home',
                    'label' => 'Home — Section layanan (judul)',
                    'sort' => 11,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cp_services')) {
            return;
        }

        DB::table('cp_services')
            ->where('title', 'Premarital Financial Health Check Up')
            ->delete();

        DB::table('cp_services')
            ->where('title', 'Financial Education Platform')
            ->update(['sort' => 4]);

        DB::table('cp_services')
            ->where('title', 'Financial Recovery Program')
            ->update(['sort' => 5]);

        DB::table('cp_services')
            ->where('title', 'like', 'Education Financing%')
            ->update(['sort' => 6]);

        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('key', 'home.services_title')
                ->update(['value' => 'Enam Layanan Kesehatan Finansial']);
        }
    }
};
