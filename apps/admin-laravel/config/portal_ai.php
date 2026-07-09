<?php

return [
    'enabled' => (bool) env('PORTAL_AI_ENABLED', env('FTSA_AI_ENABLED', true)),

    'provider' => env('PORTAL_AI_PROVIDER', 'claude'),

    'api_key' => env('ANTHROPIC_API_KEY', env('GEMINI_API_KEY')),

    'api_version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),

    'models' => array_values(array_filter(array_map(
        fn (string $v) => trim($v),
        explode(',', (string) env('PORTAL_AI_MODELS', env('FTSA_AI_MODELS', 'claude-haiku-4-5,claude-sonnet-4-6')))
    ))),

    'system_prompt' => 'Balas hanya dengan JSON valid tanpa markdown atau penjelasan tambahan.',

    'max_tokens' => (int) env('PORTAL_AI_MAX_TOKENS', 2048),

    'temperature' => (float) env('PORTAL_AI_TEMPERATURE', env('FTSA_AI_TEMPERATURE', 0.3)),
    'timeout_seconds' => (int) env('PORTAL_AI_TIMEOUT', env('FTSA_AI_TIMEOUT', 45)),

    'cache_ttl_days_ftsa' => (int) env('PORTAL_AI_CACHE_DAYS_FTSA', 30),
    'cache_ttl_hours_dashboard' => (int) env('PORTAL_AI_CACHE_HOURS_DASHBOARD', 24),

    'guidance_timezone' => env('PORTAL_AI_GUIDANCE_TZ', 'Asia/Jakarta'),
    'guidance_weekly_time' => env('PORTAL_AI_GUIDANCE_WEEKLY_TIME', '22:00'),
    'guidance_monthly_time' => env('PORTAL_AI_GUIDANCE_MONTHLY_TIME', '22:00'),
    'guidance_weekly_label' => 'Minggu pukul 22.00 WIB',

    'max_insights' => 3,
    'max_recommendations' => 3,
    'max_general_recommendations' => 3,
    'max_findings' => 5,

    'shared_rules' => [
        'Gunakan Bahasa Indonesia yang hangat, profesional, dan tidak menghakimi.',
        'Hanya merujuk data yang diberikan — jangan mengarang angka, kategori, atau diagnosis medis.',
        'Fokus pada kesadaran behavioral finansial, bukan saran investasi spesifik atau produk keuangan.',
        'Jangan menyebut bahwa Anda adalah AI; tulis seolah dr. Financial dari YFD.',
        'Hindari kalimat absolut ("selalu", "pasti"); gunakan nuansa probabilistik.',
        'Rekomendasi harus konkret, bisa dilakukan dalam 1–2 minggu.',
    ],

    'ftsa_rules' => [
        'Insight menjelaskan pola perilaku yang mungkin muncul dari archetype dan skor domain FTSA.',
        'Jika skor domain tinggi (≥25/40), tekankan risiko dysregulation tanpa menakut-nakuti.',
        'Jika skor rendah, tekankan penguatan kebiasaan positif yang sudah ada.',
    ],

    'financial_stage_rules' => [
        'Gunakan playbook tahap keuangan YFD sebagai sumber kebenaran — jangan mengubah makna fase.',
        'Personalisasi ringkasan dengan merujuk jawaban diagnostik user (dana darurat, utang, proteksi, investasi).',
        'Jangan memberi saran produk investasi/asuransi spesifik atau janji return.',
        'Nada seperti FMR manual YFD: hangat, membangun, seperti dokter finansial.',
    ],

    'behavioral_rules' => [
        'Hubungkan pola mood, impulsivitas, dan profil FTSA jika tersedia.',
        'Behavioral summary = ringkasan deskriptif kumulatif mingguan (bukan rekomendasi). Contoh: "Sekitar 36 transaksi (30,3%) bersifat impulsif.", "Saat mood lelah, 100% transaksi impulsif; saat stres 66% impulsif.", "Mood netral mendominasi transaksi terbanyak (60 transaksi)."',
        'Insight = interpretasi korelasi FTSA (mis. impulsif saat lelah + SSD Severe) dan risiko finansial jika tidak diatur.',
        'Behavioral recommendation = rekomendasi tindakan bulanan yang menghubungkan FTSA dengan pola transaksi (mis. enough number, hari libur, passive income untuk Overworker/SSD).',
        'Rekomendasi personal spesifik untuk kondisi user; hindari mengulang ringkasan deskriptif di rekomendasi.',
    ],

    'financial_rules' => [
        'Clinical summary merangkum kondisi deskriptif arus kas dan bucket prescription — kumulatif dari awal bulan.',
        'Doctor\'s note HANYA berisi rekomendasi tindakan praktis — jangan mengulang ringkasan deskriptif clinical summary.',
        'Rekomendasi doctor\'s note harus spesifik dan dapat ditindaklanjuti (alokasi bucket, saving rate, diversifikasi, proteksi).',
        'Status clinical_summary harus salah satu: healthy, fair, attention, critical.',
    ],

    'archetype_fallback' => [
        'controller' => [
            'insight' => 'Pola Controller cenderung mengaitkan rasa aman dengan kontrol penuh atas angka dan keputusan keuangan.',
            'recommendation' => 'Coba delegasikan satu keputusan keuangan rutin per minggu dan amati apakah kecemasan benar-benar meningkat.',
        ],
        'avoider' => [
            'insight' => 'Pola Avoider sering menunda paparan informasi keuangan untuk menghindari ketidaknyamanan emosional.',
            'recommendation' => 'Jadwalkan 10 menit mingguan hanya untuk melihat ringkasan keuangan — tanpa harus mengambil tindakan besar.',
        ],
        'overworker' => [
            'insight' => 'Pola Overworker sering mengaitkan nilai diri dan keamanan dengan produktivitas berkelanjutan.',
            'recommendation' => 'Tetapkan satu aktivitas istirahat terjadwal minggu ini tanpa merasa bersalah secara finansial.',
        ],
        'impulsive' => [
            'insight' => 'Pola Impulsive rentan menggunakan belanja sebagai regulator emosi jangka pendek.',
            'recommendation' => 'Terapkan aturan jeda 24 jam untuk pembelian di luar daftar belanja mingguan.',
        ],
    ],
];
