<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Homepage copy → Site Settings (group home) + update About bg_p3 sesuai klien.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key' => 'home.bg_eyebrow',
                'value' => 'LATAR BELAKANG',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Latar Belakang (label kecil)',
                'sort' => 1,
            ],
            [
                'key' => 'home.bg_title',
                'value' => 'Indonesia bukan negara termiskin, tapi sebagian besar belum sehat secara finansial.',
                'type' => 'textarea',
                'group' => 'home',
                'label' => 'Home — Latar Belakang (judul)',
                'sort' => 2,
            ],
            [
                'key' => 'home.bg_p1',
                'value' => 'Berdasarkan data BPS 2025, 82,2% masyarakat Indonesia merupakan kelompok ekonomi menengah ke bawah. Sedikit guncangan ekonomi saja sudah berdampak luas. Akar masalahnya bukan hanya karena rendahnya literasi keuangan/pengetahuan tapi juga kurangnya regulasi diri/self awareness dalam mengambil keputusan finansial yang sehat.',
                'type' => 'textarea',
                'group' => 'home',
                'label' => 'Home — Latar Belakang (paragraf 1)',
                'sort' => 3,
            ],
            [
                'key' => 'home.bg_p2',
                'value' => 'YFD lahir untuk menjadi "dokter dompet" — membantu masyarakat memahami kondisi finansial mereka secara objektif, dan meningkatkan kekebalan komunitas (Herd Financial Immunity).',
                'type' => 'textarea',
                'group' => 'home',
                'label' => 'Home — Latar Belakang (paragraf 2)',
                'sort' => 4,
            ],
            [
                'key' => 'home.bg_cta',
                'value' => 'Pelajari filosofi YFD',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Link ke Tentang',
                'sort' => 5,
            ],
            [
                'key' => 'home.services_eyebrow',
                'value' => 'EKOSISTEM YFD',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Section layanan (label)',
                'sort' => 10,
            ],
            [
                'key' => 'home.services_title',
                'value' => 'Enam Layanan Kesehatan Finansial',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Section layanan (judul)',
                'sort' => 11,
            ],
            [
                'key' => 'home.services_subtitle',
                'value' => 'Mengintegrasikan edukasi, proteksi, pendampingan, dan solusi finansial dalam satu ekosistem.',
                'type' => 'textarea',
                'group' => 'home',
                'label' => 'Home — Section layanan (deskripsi)',
                'sort' => 12,
            ],
            [
                'key' => 'home.services_cta',
                'value' => 'Lihat semua layanan',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Tombol ke halaman Layanan',
                'sort' => 13,
            ],
            [
                'key' => 'home.trust_1',
                'value' => 'Dokter umum bersertifikat QWP',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Trust badge 1',
                'sort' => 20,
            ],
            [
                'key' => 'home.trust_2',
                'value' => 'Pendekatan medis untuk finansial',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Trust badge 2',
                'sort' => 21,
            ],
            [
                'key' => 'home.trust_3',
                'value' => 'Building Financially Healthy Generations',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — Trust badge 3',
                'sort' => 22,
            ],
            [
                'key' => 'home.cta_title',
                'value' => 'Edukasi Saja Belum Cukup?',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Home — CTA bawah (judul) — jika dipakai',
                'sort' => 30,
            ],
            [
                'key' => 'wealthpedia.cat_title',
                'value' => 'Kategori Edukasi',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Wealthpedia — Judul kategori',
                'sort' => 40,
            ],
            [
                'key' => 'wealthpedia.cat_subtitle',
                'value' => 'Temukan artikel berdasarkan bidang pembahasan. Setiap kategori dirancang untuk membantu Anda membangun kesehatan finansial secara menyeluruh, mulai dari pola pikir, perilaku, hingga strategi keuangan.',
                'type' => 'textarea',
                'group' => 'home',
                'label' => 'Wealthpedia — Subjudul kategori',
                'sort' => 41,
            ],
            [
                'key' => 'wealthpedia.cat_bf_desc',
                'value' => 'Memahami bagaimana emosi, kebiasaan, bias kognitif, dan proses pengambilan keputusan memengaruhi cara kita menggunakan uang dalam kehidupan sehari-hari.',
                'type' => 'textarea',
                'group' => 'home',
                'label' => 'Wealthpedia — Deskripsi Behavioural Finance',
                'sort' => 42,
            ],
            [
                'key' => 'wealthpedia.cat_fh_desc',
                'value' => 'Pelajari prinsip-prinsip membangun kondisi finansial yang sehat, stabil, dan berkelanjutan.',
                'type' => 'textarea',
                'group' => 'home',
                'label' => 'Wealthpedia — Deskripsi Financial Health',
                'sort' => 43,
            ],
        ];

        foreach ($rows as $row) {
            Setting::updateOrCreate(
                ['key' => $row['key']],
                $row
            );
        }

        // Sync About paragraf 3 dengan pesan klien (halaman Tentang).
        Setting::query()->where('key', 'about.bg_p3')->update([
            'value' => 'Pendidikan finansial adalah kunci naik kelas dan keluar dari kemiskinan. Sebanyak 49% masyarakat menengah-bawah sedang menuju kelas menengah dan menjadi tulang punggung Indonesia. Akar masalahnya bukan hanya karena rendahnya literasi keuangan/pengetahuan tapi juga kurangnya regulasi diri/self awareness dalam mengambil keputusan finansial yang sehat.',
            'updated_at' => now(),
        ]);

        Setting::bust();
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', [
            'home.bg_eyebrow',
            'home.bg_title',
            'home.bg_p1',
            'home.bg_p2',
            'home.bg_cta',
            'home.services_eyebrow',
            'home.services_title',
            'home.services_subtitle',
            'home.services_cta',
            'home.trust_1',
            'home.trust_2',
            'home.trust_3',
            'home.cta_title',
            'wealthpedia.cat_title',
            'wealthpedia.cat_subtitle',
            'wealthpedia.cat_bf_desc',
            'wealthpedia.cat_fh_desc',
        ])->delete();

        Setting::bust();
    }
};
