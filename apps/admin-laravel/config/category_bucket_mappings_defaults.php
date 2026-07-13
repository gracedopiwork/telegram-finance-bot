<?php

/**
 * Template pemetaan Kategori → Bucket — selaras taxonomy bot Telegram.
 * Sub kategori tidak dipakai (isi '-').
 */
return [
    // Saving instruments
    ['category' => 'Saham', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'saving', 'nature' => 'Need', 'match_keywords' => 'saham,avg down,beli saham', 'reason' => 'Investasi saham', 'sort_order' => 6],
    ['category' => 'Reksadana', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'saving', 'nature' => 'Need', 'match_keywords' => 'reksadana,reksa dana,bibit', 'reason' => 'Investasi reksadana', 'sort_order' => 7],
    ['category' => 'Obligasi', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'saving', 'nature' => 'Need', 'match_keywords' => 'obligasi,sbn', 'reason' => 'Investasi obligasi/SBN', 'sort_order' => 8],
    ['category' => 'Emas', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'saving', 'nature' => 'Need', 'match_keywords' => 'emas,antam,beli emas', 'reason' => 'Investasi emas', 'sort_order' => 9],
    ['category' => '*', 'bucket' => 'Future Building', 'transaction_type' => 'saving', 'reason' => 'Semua nabung & investasi', 'sort_order' => 5],

    // Income
    ['category' => 'Gaji', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'gaji,salary,payroll,slip gaji', 'reason' => 'Pemasukan gaji', 'sort_order' => 10],
    ['category' => 'Bonus', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'bonus,thr,tunjangan hari raya,insentif', 'reason' => 'Bonus/THR/insentif', 'sort_order' => 11],
    ['category' => 'Freelance', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'freelance,honor,honorarium,fee project', 'reason' => 'Honor/freelance', 'sort_order' => 12],
    ['category' => 'Affiliate', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'affiliate,afiliasi,komisi,commission,referral,shopee affiliate,tiktok affiliate', 'reason' => 'Komisi affiliate marketplace', 'sort_order' => 13],
    ['category' => 'Dividen', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'dividen,dividend', 'reason' => 'Dividen yang dicairkan', 'sort_order' => 14],
    ['category' => 'Bunga Investasi', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'bunga investasi,bunga deposito,bunga tabungan,terima bunga,dapat bunga,interest,kupon obligasi,kupon sbn', 'reason' => 'Bunga/hasil investasi yang dicairkan', 'sort_order' => 15],
    ['category' => 'Cashback', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'cashback,cash back,rebate,redeem poin', 'reason' => 'Cashback/poin', 'sort_order' => 16],
    ['category' => 'Refund', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'refund,pengembalian dana,chargeback', 'reason' => 'Pengembalian dana', 'sort_order' => 17],
    ['category' => 'Penjualan', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'hasil jualan,hasil penjualan,omzet,jual barang', 'reason' => 'Hasil penjualan aset/barang', 'sort_order' => 18],
    ['category' => 'Sewa Masuk', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'terima sewa,hasil sewa,rental income,uang kos masuk', 'reason' => 'Pendapatan sewa', 'sort_order' => 19],
    ['category' => 'Transfer Masuk', 'sub_category' => '-', 'bucket' => 'Income', 'transaction_type' => 'income', 'nature' => 'Need', 'match_keywords' => 'transfer masuk,kiriman orang tua,kiriman keluarga', 'reason' => 'Transfer masuk / kiriman', 'sort_order' => 20],

    // Essential Living — konteks prioritas
    ['category' => 'Makan', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'starbucks,meeting,kerja,rapat,klien,ngopi meeting', 'reason' => 'Kopi/pertemuan kerja — esensial', 'sort_order' => 25],
    ['category' => 'Elektronik', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'hp rusak,handphone rusak,ganti hp,hp pecah,layar pecah,hp mati', 'reason' => 'Penggantian HP utama rusak', 'sort_order' => 26],
    ['category' => 'Elektronik', 'sub_category' => '-', 'bucket' => 'Future Building', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'laptop kerja,laptop produktif,alat kerja,untuk kerja,modal kerja', 'reason' => 'Alat produktif / laptop kerja', 'sort_order' => 27],
    ['category' => 'Jajan', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'fomo,healing,upgrade karena', 'reason' => 'Belanja impulsif / FOMO', 'sort_order' => 28],

    // Essential Living
    ['category' => 'Makan', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'makan,restoran,nasi,sarapan', 'reason' => 'Kebutuhan makan harian', 'sort_order' => 30],
    ['category' => 'Transport', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'ojek,grab,gojek,angkot,bensin,tol,parkir,servis,bengkel', 'reason' => 'Transportasi harian', 'sort_order' => 31],
    ['category' => 'Sewa/Tempat Tinggal', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'bayar sewa,sewa kos,kontrakan,kpr,cicilan rumah', 'reason' => 'Tempat tinggal', 'sort_order' => 32],
    ['category' => 'Pendidikan', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'spp,uang sekolah,kursus,les,bimbel,kuliah', 'reason' => 'Pendidikan', 'sort_order' => 33],
    ['category' => 'Komunikasi', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'pulsa,kuota,paket data,wifi,indihome', 'reason' => 'Komunikasi/internet', 'sort_order' => 34],
    ['category' => 'Cicilan', 'sub_category' => '-', 'bucket' => 'Essential Living', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'cicilan,angsuran,paylater,kredit motor', 'reason' => 'Cicilan/kredit', 'sort_order' => 35],

    // Protection
    ['category' => 'Listrik', 'sub_category' => '-', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'listrik,pln,token', 'reason' => 'Tagihan listrik', 'sort_order' => 40],
    ['category' => 'Air', 'sub_category' => '-', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'pdam,tagihan air,rekening air', 'reason' => 'Tagihan air/utilitas', 'sort_order' => 41],
    ['category' => 'Asuransi', 'sub_category' => '-', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'asuransi,premi,bpjs', 'reason' => 'Proteksi asuransi/BPJS', 'sort_order' => 42],
    ['category' => 'Kesehatan', 'sub_category' => '-', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'obat,apotek,klinik,dokter,rumah sakit,vitamin', 'reason' => 'Kesehatan', 'sort_order' => 43],
    ['category' => 'Pajak', 'sub_category' => '-', 'bucket' => 'Protection', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'pajak,pph,ppn,pbb,stnk,samsat', 'reason' => 'Pajak/administrasi', 'sort_order' => 44],

    // Flexible + Social
    ['category' => 'Jajan', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'kopi,coffee,jajan,snack,boba', 'reason' => 'Jajan & kopi', 'sort_order' => 50],
    ['category' => 'Elektronik', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'headset,earphone,earbuds,airpods,gadget,charger,powerbank,mouse,keyboard,speaker,monitor,tablet,laptop,hp,handphone,iphone,smartphone', 'reason' => 'Gadget & elektronik (bukan jajan)', 'sort_order' => 54],
    ['category' => 'Peralatan', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'tumbler,botol minum,peralatan,rumah tangga,pisau,panci,sendok', 'reason' => 'Peralatan rumah / lifestyle (bukan jajan)', 'sort_order' => 55],
    ['category' => 'Skincare', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'skincare,skin care,serum,moisturizer', 'reason' => 'Perawatan diri', 'sort_order' => 51],
    ['category' => 'Subscription', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Wants', 'match_keywords' => 'subscription,langganan,netflix,spotify,youtube premium', 'reason' => 'Langganan digital', 'sort_order' => 52],
    ['category' => 'Social', 'sub_category' => '-', 'bucket' => 'Flexible + Social', 'transaction_type' => 'expense', 'nature' => 'Need', 'match_keywords' => 'hadiah,amplop,sedekah,persembahan,ibadah,donasi,zakat', 'reason' => 'Sosial & donasi', 'sort_order' => 53],
];
