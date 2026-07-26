<?php

/**
 * Template pemetaan Kategori → Bucket — selaras YFD AI Taxonomy v1.0.
 * Sub kategori dipakai internal untuk mapping; dashboard hanya tampilkan kategori.
 */
return [
    // Saving instruments
    ['category' => '*', 'sub_category' => '-', 'bucket' => 'Protection', 'transaction_type' => 'saving', 'nature' => 'Need', 'match_keywords' => 'dana darurat,emergency fund,top up emergency,topup emergency', 'reason' => 'Dana darurat = proteksi', 'sort_order' => 1],
    ['category' => 'Investasi & Tabungan', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'saving', 'nature' => 'Need', 'match_keywords' => 'saham,reksadana,emas,obligasi,deposito,crypto,nabung,investasi', 'reason' => 'Investasi & tabungan', 'sort_order' => 5],
    ['category' => '*', 'bucket' => 'Future Building', 'transaction_type' => 'saving', 'reason' => 'Default saving', 'sort_order' => 6],

    // Income
    ['category' => 'Gaji', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'gaji,salary,payroll', 'reason' => 'Pemasukan gaji', 'sort_order' => 10],
    ['category' => 'Bonus', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'bonus,thr,insentif', 'reason' => 'Bonus/THR', 'sort_order' => 11],
    ['category' => 'Freelance', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'freelance,honor,honorarium', 'reason' => 'Honor/freelance', 'sort_order' => 12],
    ['category' => 'Affiliate', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'affiliate,afiliasi,komisi,commission,referral', 'reason' => 'Komisi affiliate', 'sort_order' => 13],
    ['category' => 'Dividen', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'dividen,dividend', 'reason' => 'Dividen cair', 'sort_order' => 14],
    ['category' => 'Bunga Investasi', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'bunga,interest', 'reason' => 'Bunga investasi', 'sort_order' => 15],
    ['category' => 'Cashback', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'cashback', 'reason' => 'Cashback', 'sort_order' => 16],
    ['category' => 'Refund', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'refund,pengembalian', 'reason' => 'Refund', 'sort_order' => 17],
    ['category' => 'Penjualan', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'hasil jualan,omzet', 'reason' => 'Hasil penjualan', 'sort_order' => 18],
    ['category' => 'Sewa Masuk', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'terima sewa,hasil sewa', 'reason' => 'Pendapatan sewa', 'sort_order' => 19],
    ['category' => 'Transfer Masuk', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'transfer masuk,kiriman', 'reason' => 'Transfer masuk', 'sort_order' => 20],

    // Context overrides
    ['category' => '*', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'match_keywords' => 'konsumsi meeting,take konten,konten bisnis,meeting bisnis,rapat bisnis,bisnis yfd,modal usaha,website bisnis,software bisnis,marketing bisnis,chatgpt,claude,notion,canva pro,figma', 'reason' => 'Biaya bisnis / tools kerja', 'sort_order' => 20],
    ['category' => 'Pendidikan', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'spp,ukt,uang sekolah,buku pelajaran,uang kuliah', 'reason' => 'Pendidikan wajib (prioritas sebelum self-dev)', 'sort_order' => 21],
    ['category' => 'Pendidikan', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'match_keywords' => 'seminar,workshop,kursus,sertifikasi,conference,pengembangan diri,coaching,mentoring,les,kelas,buku,belajar,public speaking,piano', 'reason' => 'Pengembangan diri selalu Future Building', 'sort_order' => 22],
    ['category' => 'Makanan & Minuman', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'meeting kerja,rapat kerja,meeting klien,ngopi meeting,kopi meeting', 'reason' => 'Konsumsi meeting kerja', 'sort_order' => 23],
    ['category' => 'Lifestyle & Hiburan', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'hp utama rusak,hp rusak,handphone rusak,ganti hp,hp pecah,fisioterapi,rehab', 'reason' => 'HP utama rusak / rehab medis', 'sort_order' => 24],
    ['category' => 'Bisnis & Karir', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'modal usaha,marketing,ads,website,domain,hosting,laptop kerja,tools kerja', 'reason' => 'Bisnis & karir', 'sort_order' => 25],

    // Essential Living
    ['category' => 'Makanan & Minuman', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'makan,nasi,sarapan,bahan makanan,air minum,galon', 'reason' => 'Kebutuhan makan harian', 'sort_order' => 30],
    ['category' => 'Tempat Tinggal', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'kos,kontrakan,kpr,listrik,pln,pdam,gas,ipl,wifi rumah,detergen,laundry,cuci baju', 'reason' => 'Tempat tinggal & utilitas', 'sort_order' => 31],
    ['category' => 'Transportasi', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'ojek,grab,gojek,bensin,tol,parkir,krl,angkot', 'reason' => 'Transportasi rutin', 'sort_order' => 32],
    ['category' => 'Komunikasi', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'pulsa,kuota,paket data', 'reason' => 'Komunikasi', 'sort_order' => 33],
    ['category' => 'Kesehatan & Kebersihan Diri', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'dokter,obat,apotek,lab,medical,vitamin,sabun,shampo,pasta gigi,softex,sunscreen', 'reason' => 'Medis & kebersihan dasar', 'sort_order' => 34],
    ['category' => 'Cicilan & Hutang', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'cicilan,angsuran,paylater,kartu kredit', 'reason' => 'Cicilan konsumtif / kewajiban', 'sort_order' => 36],

    // Protection
    ['category' => 'Proteksi', 'sub_category' => '-', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'asuransi,premi,bpjs', 'reason' => 'Premi asuransi/BPJS', 'sort_order' => 40],

    // Flexible + Social
    ['category' => 'Makanan & Minuman', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'kopi,coffee,jajan,snack,boba,cafe,resto,kuliner', 'reason' => 'Jajan & kuliner', 'sort_order' => 50],
    ['category' => 'Transportasi', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'taksi bandara,sewa kendaraan wisata', 'reason' => 'Transportasi non-rutin', 'sort_order' => 51],
    ['category' => 'Lifestyle & Hiburan', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'netflix,spotify,gaming,bioskop,konser,hobi,gym,yoga,pilates,crossfit,fashion,gadget,skincare,make up,parfum', 'reason' => 'Lifestyle & hiburan', 'sort_order' => 52],
    ['category' => 'Lifestyle & Hiburan', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'reason' => 'Default lifestyle wants', 'sort_order' => 57],
    ['category' => 'Bisnis & Karir', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'reason' => 'Default bisnis & karir', 'sort_order' => 26],
    ['category' => 'Traveling', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'liburan,staycation,hotel,tiket pesawat,wisata', 'reason' => 'Traveling', 'sort_order' => 53],
    ['category' => 'Sosial & Keluarga', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'donasi,sedekah,persembahan,perpuluhan,zakat,bantu keluarga,transfer ortu', 'reason' => 'Sosial & keluarga', 'sort_order' => 54],
    ['category' => 'Hadiah', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'hadiah,kado,parcel,souvenir,tip,tips', 'reason' => 'Hadiah', 'sort_order' => 55],
    ['category' => 'Lain-lain', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'reason' => 'Tidak terklasifikasi', 'sort_order' => 99],
];
