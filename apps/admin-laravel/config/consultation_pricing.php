<?php

/**
 * Tarif konsultasi YFD (berbayar, per sesi 1 jam) + overtime.
 * Sumber: Brief payment gateway PIVOT Prioritas + price sheet founder (Batch 1, 2026-10-25).
 * Screening / Financial Health Check-Up = GRATIS (lihat /check-up).
 *
 * Tarif sesi yang DITAGIH hanya dari file ini (`services` + `stages.*.session_price`);
 * Site Settings admin tidak mengubah tagihan sesi. Ubah tarif = ubah file ini + deploy.
 */
return [
    'standard_from' => 100_000,
    'recovery_from' => 150_000,
    'period' => '/sesi (1 jam)',
    'max_session_hours' => 2,
    'included_hours' => 1,
    'fhcu_valid_months' => 3,
    'multi_session_note' => 'Satu kasus bisa membutuhkan lebih dari satu pertemuan — tim YFD akan menjelaskan rencana sesi setelah screening.',
    'overtime_disclosure' => 'Sesi standar 1 jam. Jika sesi diperpanjang (maks. 2 jam total), dikenakan biaya Over time sesuai tahap finansial Anda — ditagihkan via payment gateway setelah sesi.',

    // Satuan tarif yang tampil di halaman booking.
    'session_unit' => '/jam',

    /*
     * Aturan tarif per layanan (service_type) dan jenis sesi (session_role; 'default' bila tanpa role).
     * rule 'fhcu_stage' = tarif tahap FHCU email customer (lihat `stages`); rule 'fixed' = `price`.
     * Kombinasi yang tidak ada di sini ditolak saat booking.
     */
    'services' => [
        'standard' => [
            'default' => ['rule' => 'fhcu_stage', 'label' => 'Financial Consultation — sesi 1 jam'],
        ],
        'premarital' => [
            'male' => ['rule' => 'fhcu_stage', 'label' => 'Premarital — sesi 1-on-1 male'],
            'female' => ['rule' => 'fhcu_stage', 'label' => 'Premarital — sesi 1-on-1 female'],
            'couple' => ['rule' => 'fixed', 'price' => 500_000, 'label' => 'Premarital — sesi couple + 1× follow-up'],
        ],
        'recovery' => [
            'default' => ['rule' => 'fixed', 'price' => 150_000, 'label' => 'Financial Recovery — konsultasi awal'],
        ],
    ],

    'stages' => [
        'surviving' => [
            'label' => 'Surviving',
            'phase' => 'Fase 1',
            'price_min' => 100_000,
            'price_max' => 100_000,
            'session_price' => 100_000,
            'overtime_price' => 50_000,
            'description' => 'Konsultasi awal untuk kondisi darurat finansial — fokus stabilisasi dan langkah bertahan.',
        ],
        'growing' => [
            'label' => 'Growing',
            'phase' => 'Fase 2',
            'price_min' => 250_000,
            'price_max' => 250_000,
            'session_price' => 250_000,
            'overtime_price' => 250_000,
            'description' => 'Pendampingan pemulihan & perbaikan struktur keuangan menuju fondasi yang lebih kuat.',
        ],
        'steady' => [
            'label' => 'Steady',
            'phase' => 'Fase 3',
            'price_min' => 500_000,
            'price_max' => 500_000,
            'session_price' => 500_000,
            'overtime_price' => 250_000,
            'description' => 'Optimalisasi keuangan, akumulasi aset, dan perencanaan jangka menengah.',
        ],
        'comfortable' => [
            'label' => 'Comfortable',
            'phase' => 'Fase 4',
            'price_min' => 1_500_000,
            'price_max' => 1_500_000,
            'session_price' => 1_500_000,
            'overtime_price' => 500_000,
            'description' => 'Perencanaan kebebasan finansial, warisan, dan stewardship jangka panjang.',
        ],
    ],
];
