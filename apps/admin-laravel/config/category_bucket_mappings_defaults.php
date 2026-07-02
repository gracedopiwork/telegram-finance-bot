<?php

/**
 * Template pemetaan Kategori → Bucket (acuan Sheet YFD First Aid).
 * Disinkronkan ke DB lewat admin → Sync Default atau seeder.
 */
return [
    // Income
    ['category' => 'Gaji', 'bucket' => 'Income', 'transaction_type' => 'income', 'reason' => 'Pendapatan utama dari pekerjaan', 'sort_order' => 10],
    ['category' => 'Dividen', 'bucket' => 'Income', 'transaction_type' => 'income', 'reason' => 'Pendapatan pasif dari investasi', 'sort_order' => 11],
    ['category' => 'Lain-lain', 'bucket' => 'Income', 'transaction_type' => 'income', 'reason' => 'Pemasukan lain di luar gaji/dividen', 'sort_order' => 12],

    // Essential Living
    ['category' => 'Makan', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'reason' => 'Kebutuhan makan harian', 'sort_order' => 20],
    ['category' => 'Makanan', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'reason' => 'Kebutuhan dasar harian', 'sort_order' => 21],
    ['category' => 'Transport', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'reason' => 'Transportasi kebutuhan', 'sort_order' => 22],
    ['category' => 'Transportasi', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'reason' => 'Ongkos/transport harian', 'sort_order' => 23],
    ['category' => 'Pulsa', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'reason' => 'Komunikasi dasar', 'sort_order' => 24],
    ['category' => 'Tagihan', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'reason' => 'Tagihan rutin rumah tangga', 'sort_order' => 25],
    ['category' => 'Listrik', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'reason' => 'Utilitas — bucket proteksi/utilitas', 'sort_order' => 26],
    ['category' => 'Air', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'reason' => 'Utilitas air', 'sort_order' => 27],

    // Protection
    ['category' => 'Asuransi', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'match_keywords' => 'bpjs,asuransi,premi,dana darurat', 'reason' => 'Proteksi finansial & risiko', 'sort_order' => 30],
    ['category' => 'Admin bank', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'reason' => 'Biaya perbankan', 'sort_order' => 31],

    // Future Building
    ['category' => 'INVESTASI', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'nature' => 'Saving/Investement', 'reason' => 'Investasi & pertumbuhan aset', 'sort_order' => 40],
    ['category' => 'Investasi', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'match_keywords' => 'saham,reksa,obligasi,emas,deposito,nabung,pelatihan,sertifikasi', 'reason' => 'Modal masa depan', 'sort_order' => 41],

    // Flexible + Social
    ['category' => 'Jajan', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'jajan,kopi,cafe,restoran,hiburan', 'reason' => 'Pengeluaran fleksibel / lifestyle', 'sort_order' => 50],
    ['category' => 'Social', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'reason' => 'Sosial & relasi', 'sort_order' => 51],
    ['category' => 'Pakaian', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'reason' => 'Fashion & gaya hidup', 'sort_order' => 52],
    ['category' => 'Hiburan', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'reason' => 'Rekreasi & healing', 'sort_order' => 53],
    ['category' => 'Hadiah', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'reason' => 'Pemberian & sosial', 'sort_order' => 54],

    // Nature-based overrides (tanpa kategori spesifik)
    ['category' => '*', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'nature' => 'Saving/Investement', 'reason' => 'Sifat nabung/investasi', 'sort_order' => 5],
    ['category' => '*', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Donation', 'reason' => 'Donasi & sedekah', 'sort_order' => 6],
    ['category' => '*', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'jajan,kopi,liburan,hadiah,donasi', 'reason' => 'Keinginan / wants', 'sort_order' => 7],
];
