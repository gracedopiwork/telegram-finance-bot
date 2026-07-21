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
        'cicilan modal usaha', 'pengembangan diri', 'buku pengembangan diri',
        'psychology of money', 'psychologi of money', 'self development', 'les', 'coaching',
        'mentoring', 'kelas piano', 'les piano', 'kursus piano', 'les musik', 'kelas musik',
        'les vokal', 'les bahasa', 'kursus bahasa', 'public speaking',
        'belajar piano', 'piano untuk belajar', 'latihan piano', 'alat musik untuk belajar',
        'alat musik untuk profesi', 'profesi musik', 'untuk manggung', 'studio musik',
    ],
    'flexible_keywords' => [
        'jajan', 'kopi', 'coffee', 'cafe', 'nongkrong', 'healing',
        'liburan', 'staycation', 'bioskop', 'konser', 'hobi', 'hadiah', 'donasi', 'sedekah',
        'persembahan', 'perpuluhan', 'streaming', 'gaming', 'fashion', 'skincare', 'skin care',
        'make up', 'fomo', 'subscription', 'langganan', 'netflix', 'spotify',
    ],
    'essential_context_keywords' => [
        'hp rusak', 'handphone rusak', 'ganti hp', 'hp pecah', 'layar pecah', 'hp mati',
        'smartphone rusak', 'ganti handphone',
        'les olahraga', 'coaching olahraga', 'kursus olahraga', 'personal trainer',
        'fitness coach', 'kelas gym', 'kelas yoga', 'kelas pilates',
        'les tenis', 'coaching tenis', 'pelatih tenis', 'kelas tenis',
        'les renang', 'coaching renang', 'pelatih renang', 'kelas renang',
        'les badminton', 'les bulu tangkis', 'coaching badminton',
        'coaching basket', 'coaching sepak bola', 'coaching futsal', 'coaching golf',
    ],
    'future_building_context_keywords' => [
        'laptop kerja', 'laptop produktif', 'alat kerja', 'untuk kerja', 'modal kerja',
        'laptop kantor', 'komputer kerja', 'freelancer it', 'jasa freelancer it',
        'web developer', 'website bisnis', 'website usaha', 'website proyek',
        'software bisnis', 'software usaha', 'marketing bisnis', 'marketing usaha',
        'modal usaha', 'proyek bisnis', 'project bisnis', 'proyek yfd',
    ],
    'essential_meeting_keywords' => [
        'starbucks', 'meeting kerja', 'rapat kerja', 'meeting klien', 'ngopi meeting', 'kopi meeting',
    ],
    'essential_categories' => [
        'makan', 'transport', 'sewa/tempat tinggal', 'listrik', 'air', 'komunikasi',
        'kesehatan', 'pajak', 'pendidikan', 'cicilan',
    ],
    'bot_fallback_category' => 'Makan',
    'bot_fallback_sub' => 'Pengeluaran lain-lain',
    'source_of_truth_note' => [
        'Taxonomy terbuka: AI boleh membuat label kategori baru sesuai barang/jasa (contoh: Peralatan, Fashion, Hobi).',
        'Daftar kategori yang sudah ada hanya referensi — jangan memaksa transaksi ke Jajan jika tidak cocok.',
        'Kategori baru dari bot/import dibuat otomatis di pemetaan bucket beserta keyword dari catatan transaksi.',
        'Admin boleh menyesuaikan bucket/nature nanti; tidak perlu menambah kategori manual dulu.',
        'Need vs Wants: dengarkan niat fungsional user, bukan sekadar merek premium.',
        'Jenis transaksi dan bucket prescription adalah dua dimensi berbeda: pengeluaran bisnis tetap Pengeluaran, tetapi bucket-nya Future Building.',
        'Bucket ditentukan dari tujuan transaksi: kerja/bisnis/pengembangan diri → Future Building; risiko/asuransi/dana darurat → Protection.',
        'Semua self-development (les, kursus, coaching, musik, bahasa) → Future Building; khusus les/coaching olahraga → Essential Living.',
    ],
];
