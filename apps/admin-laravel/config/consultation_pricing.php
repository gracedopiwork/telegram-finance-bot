<?php

/**
 * Tarif konsultasi YFD (berbayar, per sesi).
 * Screening / Financial Health Check-Up = GRATIS (lihat /check-up).
 */
return [
    'standard_from' => 100_000,
    'recovery_from' => 150_000,
    'period' => '/sesi',
    'multi_session_note' => 'Satu kasus bisa membutuhkan lebih dari satu pertemuan — tim YFD akan menjelaskan rencana sesi setelah screening.',

    'stages' => [
        'surviving' => [
            'label' => 'Surviving',
            'phase' => 'Fase 1',
            'price_min' => 100_000,
            'price_max' => 100_000,
            'description' => 'Konsultasi awal untuk kondisi darurat finansial — fokus stabilisasi dan langkah bertahan.',
        ],
        'growing' => [
            'label' => 'Growing',
            'phase' => 'Fase 2',
            'price_min' => 250_000,
            'price_max' => 500_000,
            'description' => 'Pendampingan pemulihan & perbaikan struktur keuangan menuju fondasi yang lebih kuat.',
        ],
        'steady' => [
            'label' => 'Steady',
            'phase' => 'Fase 3',
            'price_min' => 500_000,
            'price_max' => 750_000,
            'description' => 'Optimalisasi keuangan, akumulasi aset, dan perencanaan jangka menengah.',
        ],
        'comfortable' => [
            'label' => 'Comfortable',
            'phase' => 'Fase 4',
            'price_min' => 1_500_000,
            'price_max' => 2_500_000,
            'description' => 'Perencanaan kebebasan finansial, warisan, dan stewardship jangka panjang.',
        ],
    ],
];
