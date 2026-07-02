<?php

return [
    'enabled' => (bool) env('FTSA_AI_ENABLED', true),

    'api_key' => env('GEMINI_API_KEY'),

    'models' => array_values(array_filter(array_map(
        fn (string $v) => trim($v),
        explode(',', (string) env('FTSA_AI_MODELS', 'gemini-2.5-flash,gemini-2.0-flash,gemini-2.0-flash-lite'))
    ))),

    'temperature' => (float) env('FTSA_AI_TEMPERATURE', 0.3),
    'timeout_seconds' => (int) env('FTSA_AI_TIMEOUT', 45),
    'max_insights' => 3,
    'max_recommendations' => 3,

    /*
    |--------------------------------------------------------------------------
    | Aturan untuk AI (dr. Financial — Your Financial Doctor)
    |--------------------------------------------------------------------------
    */
    'rules' => [
        'Gunakan Bahasa Indonesia yang hangat, profesional, dan tidak menghakimi.',
        'Hanya merujuk data skor FTSA yang diberikan — jangan mengarang angka atau diagnosis medis.',
        'Fokus pada kesadaran behavioral finansial, bukan saran investasi spesifik atau produk keuangan.',
        'Insight menjelaskan pola perilaku yang mungkin muncul dari archetype dan skor domain.',
        'Rekomendasi harus konkret, bisa dilakukan dalam 1–2 minggu, dan aman secara emosional.',
        'Jangan menyebut bahwa Anda adalah AI; tulis seolah dr. Financial dari YFD.',
        'Hindari kalimat absolut ("selalu", "pasti"); gunakan nuansa probabilistik.',
        'Jika skor domain tinggi (≥25/40), tekankan risiko dysregulation tanpa menakut-nakuti.',
        'Jika skor rendah, tekankan penguatan kebiasaan positif yang sudah ada.',
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
