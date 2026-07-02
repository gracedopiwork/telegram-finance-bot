<?php

/**
 * Template pemetaan Kategori → Bucket — selaras taxonomy bot Telegram.
 * Setiap baris category + sub_category = pasangan dropdown bot.
 */
return [
    // Income
    ['category' => 'Gaji', 'sub_category' => 'Pengeluaran lain-lain', 'bucket' => 'Income', 'transaction_type' => 'income', 'reason' => 'Pemasukan gaji/bonus/honor', 'sort_order' => 10],

    // Essential Living
    ['category' => 'Makan', 'sub_category' => 'Jajan / Makan diluar', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'makan,restoran,nasi,sarapan', 'reason' => 'Kebutuhan makan harian', 'sort_order' => 20],
    ['category' => 'Transport', 'sub_category' => 'Angkutan Umum', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'ojek,grab,gojek,angkot,bensin,tol', 'reason' => 'Transportasi harian', 'sort_order' => 21],
    ['category' => 'Transport', 'sub_category' => 'Servis Kendaraan', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'servis,bengkel,oli,ban', 'reason' => 'Perawatan kendaraan', 'sort_order' => 22],

    // Protection / utilitas
    ['category' => 'Listrik', 'sub_category' => 'Listrik', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'listrik,pln,token', 'reason' => 'Tagihan listrik', 'sort_order' => 30],
    ['category' => 'Air', 'sub_category' => 'Pengeluaran lain-lain', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'pdam,tagihan air,rekening air', 'reason' => 'Tagihan air/utilitas', 'sort_order' => 31],

    // Flexible + Social — Jajan
    ['category' => 'Jajan', 'sub_category' => 'Jajan / Makan diluar', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'kopi,coffee,jajan,snack', 'reason' => 'Jajan & kopi', 'sort_order' => 40],
    ['category' => 'Jajan', 'sub_category' => 'Skincare', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'skincare', 'reason' => 'Perawatan diri', 'sort_order' => 41],
    ['category' => 'Jajan', 'sub_category' => 'Pakaian', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'baju,pakaian,fashion', 'reason' => 'Fashion', 'sort_order' => 42],
    ['category' => 'Jajan', 'sub_category' => 'Popok', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'popok,diaper', 'reason' => 'Kebutuhan bayi', 'sort_order' => 43],
    ['category' => 'Jajan', 'sub_category' => 'Mainan Anak', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'mainan', 'reason' => 'Mainan anak', 'sort_order' => 44],
    ['category' => 'Jajan', 'sub_category' => 'Vitamin', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'vitamin,suplemen', 'reason' => 'Kesehatan preventif', 'sort_order' => 45],
    ['category' => 'Jajan', 'sub_category' => 'Alat Kesehatan', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'masker,termometer,alat kesehatan', 'reason' => 'Alat kesehatan', 'sort_order' => 46],
    ['category' => 'Jajan', 'sub_category' => 'Pengeluaran lain-lain', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'hp,laptop,gadget,elektronik', 'reason' => 'Belanja lain / gadget', 'sort_order' => 47],

    // Social
    ['category' => 'Social', 'sub_category' => 'Hadiah / Amplop sosial', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Donation', 'match_keywords' => 'hadiah,amplop,sedekah', 'reason' => 'Sosial & hadiah', 'sort_order' => 50],
    ['category' => 'Social', 'sub_category' => 'Nonton Konser', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'konser,bioskop,tiket,nonton', 'reason' => 'Hiburan sosial', 'sort_order' => 51],
    ['category' => 'Social', 'sub_category' => 'Ulang Tahun keluarga', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'ultah,ulang tahun,keluarga', 'reason' => 'Perayaan keluarga', 'sort_order' => 52],

    // Aturan global berdasarkan sifat
    ['category' => '*', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'nature' => 'Saving/Investement', 'reason' => 'Semua nabung/investasi', 'sort_order' => 5],
    ['category' => '*', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Donation', 'reason' => 'Semua donasi', 'sort_order' => 6],
];
