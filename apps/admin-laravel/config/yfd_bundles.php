<?php

/**
 * Halaman bundle layanan YFD — masing-masing punya alur & CTA sendiri.
 */
return [
    // Copy founder (requirements/YFD_Founder_Intro_Consultation_1on1.md). Tarif tidak ditulis di sini:
    // `pricing_from_stages` → YfdBundles mengisi baris dari ConsultationPriceRules (sama dengan yang ditagih).
    'konsultasi' => [
        'slug' => 'konsultasi',
        'active' => 'layanan',
        'number' => '03',
        'eyebrow' => 'Konsultasi',
        'title' => 'Consultation with Financial Planner 1-on-1',
        'icon' => 'stethoscope',
        'description_lead' => 'Setiap kondisi finansial punya cerita dan kebutuhan yang berbeda.',
        'description' => 'Melalui konsultasi personal bersama Dokter Finansial, Anda akan dibantu memahami kondisi keuangan saat ini, menemukan prioritas yang perlu diperbaiki, dan menyusun rencana finansial yang realistis sesuai kebutuhan serta tujuan hidup Anda.',
        'features_label' => 'Cakupan',
        'features' => [
            'Analisis kondisi kesehatan finansial berdasarkan data keuangan pribadi',
            'Identifikasi masalah dan skala prioritas perbaikan finansial',
            'Penyusunan personalized financial plan dan action plan',
            'Eksplorasi pola perilaku dan financial trauma yang memengaruhi keputusan keuangan',
            'Financial Medical Report (FMR) dan rencana tindak lanjut',
            'Jadwal kontrol untuk mengevaluasi perkembangan dan penyesuaian rencana',
        ],
        // ":link" diganti tautan note_link.
        'note' => 'Sebelum konsultasi, lakukan :link dahulu ya',
        'note_link' => ['label' => 'Financial Health Check-Up (FHCU)', 'route' => 'checkup.show'],
        'steps' => [
            ['title' => 'Persiapan konsultasi', 'body' => 'Setelah booking berhasil, lengkapi Financial Trauma Self-Assessment (FTSA) dan data keuangan pribadi agar sesi lebih terarah.'],
            ['title' => 'Sesi konsultasi personal', 'body' => 'Bahas kondisi keuangan, pola perilaku, prioritas, dan rencana perbaikan bersama Financial Planner.'],
            ['title' => 'Financial Medical Report & Plan', 'body' => 'Dapatkan rangkuman kondisi, rekomendasi, dan langkah konkret yang dapat mulai Anda lakukan.'],
            ['title' => 'Kontrol dan evaluasi', 'body' => 'Tinjau perkembangan untuk mengetahui apa yang sudah membaik dan apa yang masih perlu disesuaikan.'],
        ],
        'pricing_intro' => 'Tarif konsultasi mengikuti tahap kesehatan finansial Anda berdasarkan hasil Financial Health Check-Up (FHCU).',
        'pricing_from_stages' => true,
        'pricing_period' => '/jam',
        'pricing' => [],
        // Urutan tampil seperti dokumen founder: FHCU dulu, lalu Booking.
        'cta_secondary_first' => true,
        'cta_primary' => ['label' => 'Booking Konsultasi', 'route' => 'company.pertemuan'],
        'cta_secondary' => ['label' => 'Mulai Financial Health Check-Up', 'route' => 'checkup.show'],
    ],

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
        'cta_secondary' => ['label' => 'Konsultasi Reguler', 'route' => 'company.konsultasi'],
    ],

    'education' => [
        'slug' => 'education',
        'active' => 'edukasi',
        'number' => '05',
        'eyebrow' => 'Education',
        'title' => 'Financial Education Platform',
        'icon' => 'school',
        'description' => 'Pusat edukasi kesehatan Finansial YFD — webinar, workshop, komunitas belajar.',
        'features_label' => 'Cakupan',
        'features' => [
            'Behavioral Finance Education',
            'Webinar & Workshop',
            'Kelas Online',
            'Research & Journal',
            'E-book & Practical Guide',
            'Community Learning',
        ],
        'offerings' => [
            [
                'icon' => 'cast_for_education',
                'title' => 'Webinar & Seminar',
                'desc' => 'Sesi live dengan dokter finansial & praktisi — topik cashflow, utang, investasi pemula, dll.',
                'status' => 'available',
            ],
            [
                'icon' => 'groups',
                'title' => 'Workshop',
                'desc' => 'Workshop khusus sesuai kebutuhan komunitas — misalnya Pre-marital, Employee, dan topik komunitas lainnya.',
                'status' => 'available',
            ],
            [
                'icon' => 'menu_book',
                'title' => 'E-book & Practical Guide',
                'desc' => 'Panduan praktis kesehatan finansial dalam format digital.',
                'status' => 'soon',
            ],
            [
                'icon' => 'auto_stories',
                'title' => 'Jurnal',
                'desc' => 'Jurnal edukasi kesehatan finansial YFD.',
                'status' => 'soon',
            ],
        ],
        'pricing' => [],
        'footnote' => 'Untuk info jadwal webinar, workshop, dan harga terbaru, hubungi tim YFD.',
        // Bukan Wealthpedia — CTA: daftar workshop & kerjasama.
        'cta_primary' => [
            'label' => 'Daftar Workshop',
            'type' => 'wa',
            'wa_topic' => 'Daftar Workshop — Financial Education Platform',
        ],
        'cta_secondary' => [
            'label' => 'Kerjasama dengan YFD',
            'type' => 'wa',
            'wa_topic' => 'Kerjasama Edukasi dengan YFD',
        ],
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
        'cta_secondary' => ['label' => 'Lihat Detail Konsultasi Individu', 'route' => 'company.konsultasi'],
    ],
];
