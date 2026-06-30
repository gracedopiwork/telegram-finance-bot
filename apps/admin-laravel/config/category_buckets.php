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
        'persembahan', 'perpuluhan', 'streaming', 'gaming', 'fashion', 'skincare', 'make up',
        'gadget', 'fomo',
    ],
    'essential_categories' => ['makan', 'transport', 'listrik', 'air', 'gaji'],
];
