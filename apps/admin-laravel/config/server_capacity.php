<?php

/**
 * Tier kapasitas VPS & estimasi biaya sewa (IDR/bulan).
 * Sesuaikan angka dengan provider/hosting aktual YFD.
 */
return [
    'currency' => 'IDR',
    'usd_idr_rate' => (int) env('SERVER_COST_USD_IDR', 16500),

  /** Biaya API per parse transaksi (estimasi, untuk proyeksi gabungan). */
    'ai_cost_per_parse_idr' => [
        'claude_haiku' => (int) env('SERVER_AI_COST_PARSE_CLAUDE', 54),
        'gemini_flash_lite' => (int) env('SERVER_AI_COST_PARSE_GEMINI', 5),
    ],

    'default_ai_provider' => env('SERVER_COST_AI_PROVIDER', 'claude_haiku'),

    /** Tier VPS — urutan naik kapasitas. */
    'tiers' => [
        [
            'key' => 'pilot',
            'label' => 'Pilot',
            'max_active_users' => 500,
            'vcpu' => 2,
            'ram_gb' => 4,
            'monthly_idr' => 250_000,
            'notes' => 'Cocok untuk uji coba & early adopters.',
        ],
        [
            'key' => 'growth',
            'label' => 'Growth',
            'max_active_users' => 5_000,
            'vcpu' => 4,
            'ram_gb' => 8,
            'monthly_idr' => 650_000,
            'notes' => 'Ratusan–ribuan user aktif bulanan.',
        ],
        [
            'key' => 'scale',
            'label' => 'Scale',
            'max_active_users' => 25_000,
            'vcpu' => 8,
            'ram_gb' => 16,
            'monthly_idr' => 1_800_000,
            'notes' => 'Pertimbangkan DB terpisah & cache.',
        ],
        [
            'key' => 'enterprise',
            'label' => 'Enterprise',
            'max_active_users' => 100_000,
            'vcpu' => 16,
            'ram_gb' => 32,
            'monthly_idr' => 4_500_000,
            'notes' => 'Load balancer, multi-node, DBA.',
        ],
    ],

    /** Ambang peringatan resource server. */
    'thresholds' => [
        'ram_warning_percent' => 75,
        'ram_critical_percent' => 90,
        'disk_warning_percent' => 80,
        'disk_critical_percent' => 92,
        'load_warning_multiplier' => 1.2,
        'load_critical_multiplier' => 2.0,
        'user_capacity_warning_percent' => 80,
    ],
];
