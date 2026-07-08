<?php

/**
 * Pemetaan transaksi ke 4 bucket prescription (YFD First Aid).
 * AI + nature menjadi penentu utama; keyword sebagai fallback.
 */
return [
    'protection_keywords' => [
        'bpjs', 'asuransi', 'premi asuransi', 'premi', 'dana darurat', 'emergency fund',
        'top up emergency', 'critical illness', 'income protection', 'jiwa',
    ],
    'future_building_keywords' => [
        'saham', 'reksa', 'obligasi', 'emas', 'deposito', 'crypto', 'investasi', 'nabung',
        'seminar', 'simposium', 'workshop', 'sertifikasi', 'pelatihan', 'kursus', 'conference',
        'penelitian', 'modal usaha', 'marketing usaha', 'website usaha', 'software usaha',
        'cicilan modal usaha', 'pengembangan diri',
    ],
    'flexible_keywords' => [
        'jajan', 'kopi', 'coffee', 'cafe', 'restoran', 'restaurant', 'nongkrong', 'healing',
        'liburan', 'staycation', 'bioskop', 'konser', 'hobi', 'hadiah', 'donasi', 'sedekah',
        'persembahan', 'perpuluhan', 'streaming', 'gaming', 'fashion', 'skincare', 'skin care',
        'make up', 'gadget', 'fomo', 'subscription', 'langganan', 'netflix', 'spotify',
    ],
    'essential_context_keywords' => [
        'hp rusak', 'handphone rusak', 'ganti hp', 'hp pecah', 'layar pecah', 'hp mati',
        'smartphone rusak', 'ganti handphone',
    ],
    'future_building_context_keywords' => [
        'laptop kerja', 'laptop produktif', 'alat kerja', 'untuk kerja', 'modal kerja',
        'laptop kantor', 'komputer kerja',
    ],
    'essential_meeting_keywords' => [
        'starbucks', 'meeting kerja', 'rapat kerja', 'meeting klien', 'ngopi meeting', 'kopi meeting',
    ],
    'essential_categories' => ['makan', 'transport', 'listrik', 'air', 'gaji'],
    'bot_fallback_category' => 'Jajan',
    'bot_fallback_sub' => 'Pengeluaran lain-lain',
    'source_of_truth_note' => [
        'Kategori baru dari transaksi/import/bot dibuat otomatis dan dikelompokkan ke bucket yang sesuai.',
        'Admin bisa menyesuaikan pemetaan kapan saja di tabel ini — tidak perlu menambah kategori manual dulu.',
        'Bot boleh memakai label kategori apa pun; sistem akan mencocokkan atau membuat entri baru.',
        'Need vs Wants: AI mempertimbangkan niat fungsional user (kopi untuk produktif kerja bisa Need), bukan sekadar label jajan premium.',
    ],
];
