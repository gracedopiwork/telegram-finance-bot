<?php

/**
 * Tarif konsultasi YFD (berbayar, per sesi 1 jam) + overtime.
 * Sumber: Brief payment gateway PIVOT Prioritas.
 * Screening / Financial Health Check-Up = GRATIS (lihat /check-up).
 *
 * Nominal bisa di-override dari Admin Site Settings (group pricing) bila ada.
 */
return [
    'standard_from' => 100_000,
    'recovery_from' => 150_000,
    'period' => '/sesi (1 jam)',
    'max_session_hours' => 2,
    'included_hours' => 1,
    'fhcu_valid_months' => 3,
    'multi_session_note' => 'Satu kasus bisa membutuhkan lebih dari satu pertemuan — tim YFD akan menjelaskan rencana sesi setelah screening.',
    'overtime_disclosure' => 'Sesi standar 1 jam. Jika sesi diperpanjang (maks. 2 jam total), dikenakan biaya overtime sesuai tahap finansial Anda — ditagihkan via payment gateway setelah sesi.',

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
