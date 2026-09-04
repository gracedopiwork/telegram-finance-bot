<?php

/**
 * Halaman bundle layanan YFD — masing-masing punya alur & CTA sendiri.
 */
return [
    'recovery' => [
        'slug' => 'recovery',
        'active' => 'recovery',
        'number' => '06',
        'eyebrow' => 'Recovery',
        'title' => 'Financial Recovery Program',
        'icon' => 'healing',
        'description' => 'Pelayanan intensif bagi individu yang mengalami permasalahan finansial kompleks — financial trauma, krisis hutang, burnout, atau adiksi perilaku finansial. Program ini mengintegrasikan pendekatan finansial, perilaku, dan psikologis melalui kolaborasi mitra profesional YFD.',
        'features_label' => 'Cakupan',
        'features' => [
            'Financial trauma & tekanan finansial kronis',
            'Adiksi perilaku finansial (judol, pinjol, compulsive spending)',
            'Krisis finansial pribadi & keluarga',
            'Financial burnout',
            'Pendampingan perubahan perilaku finansial',
            'Kolaborasi psikolog, psikiater, hipnoterapis & mitra profesional',
        ],
        'pricing' => [
            ['label' => 'Konsultasi awal Recovery', 'amount' => 150_000, 'note' => 'minimal 60 menit · dokter finansial YFD'],
            ['label' => 'Sesi lanjutan / mitra profesional', 'amount' => null, 'note' => 'Tarif disesuaikan kebutuhan individu'],
        ],
        'footnote' => 'Satu kasus bisa membutuhkan lebih dari satu pertemuan. Tim YFD akan menjelaskan rencana pendampingan setelah konsultasi awal.',
        'cta_primary' => ['label' => 'Booking Recovery', 'route' => 'company.pertemuan', 'query' => ['type' => 'recovery']],
        'cta_secondary' => ['label' => 'Konsultasi Reguler', 'route' => 'company.pertemuan'],
    ],

    'education' => [
        'slug' => 'education',
        'active' => 'edukasi',
        'number' => '05',
        'eyebrow' => 'Education',
        'title' => 'Financial Education Platform',
        'icon' => 'school',
        'description' => 'Pusat edukasi kesehatan finansial YFD — artikel, webinar, kelas online, e-book, dan komunitas belajar. Literasi finansial adalah hak, bukan privilese.',
        'features_label' => 'Cakupan',
        'features' => [
            'Financial Health Articles',
            'Behavioral Finance Education',
            'Webinar & Workshop',
            'Kelas Online',
            'Research & Journal',
            'E-book & Practical Guide',
            'Community Learning',
        ],
        'offerings' => [
            ['icon' => 'cast_for_education', 'title' => 'Webinar & Seminar', 'desc' => 'Sesi live dengan dokter finansial & praktisi — topik cashflow, utang, investasi pemula, dll.', 'status' => 'available'],
            ['icon' => 'play_lesson', 'title' => 'Kelas Online', 'desc' => 'Modul terstruktur yang bisa diikuti sesuai kecepatan Anda.', 'status' => 'soon'],
            ['icon' => 'menu_book', 'title' => 'E-book & Practical Guide', 'desc' => 'Panduan praktis kesehatan finansial dalam format digital.', 'status' => 'soon'],
            ['icon' => 'article', 'title' => 'Artikel & Jurnal', 'desc' => 'Konten edukatif gratis di Wealthpedia.', 'status' => 'free', 'route' => 'company.wealthpedia'],
        ],
        'pricing' => [],
        'footnote' => 'Paket webinar, kelas, dan e-book akan dijual terpisah. Untuk info jadwal & harga terbaru, hubungi tim YFD.',
        'cta_primary' => ['label' => 'Kunjungi Wealthpedia', 'route' => 'company.wealthpedia'],
        'cta_secondary' => ['label' => 'Tanya Paket Edukasi', 'type' => 'wa', 'wa_topic' => 'Financial Education Platform'],
    ],

    'premarital' => [
        'slug' => 'premarital',
        'active' => 'premarital',
        'number' => '04',
        'eyebrow' => 'Premarital',
        'title' => 'Premarital Financial Health Check Up',
        'icon' => 'diversity_1',
        'description' => 'Kesehatan finansial pasangan adalah pondasi rumah tangga — diperiksa sebelum menikah, bukan disesali sesudahnya. Check-up pasangan mencakup sesi individu (male & female) lalu sesi couple + follow-up dengan dokter yang sama.',
        'features_label' => 'Cakupan',
        'features' => [
            'Analisis kondisi finansial masing-masing individu (male & female terpisah)',
            'Diskusi keselarasan tujuan & nilai finansial pasangan',
            'Identifikasi red flags finansial sebelum menikah (utang tersembunyi, gaya hidup, kewajiban finansial keluarga)',
            'Financial Medical Report (FMR) untuk pasangan',
            'Rencana finansial bersama pasca-menikah',
            'Sesi follow-up evaluasi kesepakatan',
        ],
        'pricing' => [
            [
                'label' => 'Sesi 1-on-1 male',
                'amount' => null,
                'note' => 'Tarif mengikuti tahap finansial individu — lihat tabel konsultasi',
            ],
            [
                'label' => 'Sesi 1-on-1 female',
                'amount' => null,
                'note' => 'Tarif mengikuti tahap finansial individu — lihat tabel konsultasi',
            ],
            [
                'label' => 'Sesi couple + 1× follow-up',
                'amount' => 500_000,
                'note' => 'Dijadwalkan setelah kedua sesi individu selesai · dokter yang sama',
            ],
        ],
        'footnote' => 'Kedua sesi individu wajib dengan dokter yang sama, agar sesi couple punya konteks penuh dari kedua individu.',
        'cta_primary' => ['label' => 'Booking Premarital', 'type' => 'route', 'route' => 'company.pertemuan', 'query' => ['type' => 'premarital']],
        'cta_secondary' => ['label' => 'Lihat Tarif Konsultasi Individu', 'route' => 'company.paket'],
    ],
];
