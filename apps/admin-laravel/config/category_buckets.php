<?php

/**
 * Pemetaan transaksi ke 4 bucket prescription (YFD First Aid).
 * Taxonomy tertutup — lihat config/yfd_taxonomy.php (17 kategori pengeluaran).
 */
return [
    'protection_keywords' => [
        'bpjs', 'asuransi', 'premi asuransi', 'dana darurat', 'emergency fund',
        'top up emergency', 'critical illness', 'income protection', 'asuransi jiwa',
    ],
    'future_building_keywords' => [
        'saham', 'reksa', 'obligasi', 'emas', 'deposito', 'crypto', 'investasi', 'nabung',
        'seminar', 'simposium', 'workshop', 'sertifikasi', 'pelatihan', 'kursus', 'conference',
        'penelitian', 'modal usaha', 'marketing usaha', 'website usaha', 'software usaha',
        'pengembangan diri', 'self development', 'mentoring',
        'les piano', 'les musik', 'les bahasa', 'les vokal', 'coaching karier', 'coaching leadership',
        'public speaking', 'chatgpt', 'claude', 'notion', 'canva pro', 'figma', 'laptop kerja',
        'iuran organisasi', 'keanggotaan profesi', 'iuran idi', 'bayar idi', 'asosiasi profesi',
        'psychology of money', 'buku finansial', 'buku financial',
    ],
    'flexible_keywords' => [
        'jajan', 'kopi', 'coffee', 'cafe', 'nongkrong', 'healing',
        'liburan', 'staycation', 'bioskop', 'konser', 'hobi', 'hadiah', 'donasi', 'sedekah',
        'persembahan', 'perpuluhan', 'qurban', 'kurban', 'streaming', 'gaming', 'fashion', 'skincare',
        'make up', 'fomo', 'subscription', 'langganan', 'netflix', 'spotify', 'gym', 'pilates',
        'yoga', 'tenis', 'padel', 'renang', 'personal trainer', 'coaching tenis', 'coaching padel',
        'les renang', 'kelas pilates', 'kelas yoga',
    ],
    'essential_context_keywords' => [
        'hp rusak', 'handphone rusak', 'ganti hp', 'hp pecah', 'layar pecah', 'hp mati',
        'fisioterapi', 'rehab', 'rehabilitasi', 'resep dokter',
        'tumbler rusak', 'ganti tumbler', 'tumbler ganti', 'ganti yang rusak',
        'kulkas rusak', 'perabot rusak',
    ],
    // Meeting kerja / konsumsi bisnis → Future Building (bukan Essential Living).
    'future_building_context_keywords' => [
        'laptop kerja', 'laptop produktif', 'alat kerja', 'untuk kerja', 'modal kerja',
        'freelancer it', 'website bisnis', 'software bisnis', 'marketing bisnis',
        'modal usaha', 'proyek bisnis', 'konten bisnis',
        'networking bisnis', 'ketemu client', 'ketemu klien', 'ketemu bisnis',
        'meeting client', 'meeting bisnis', 'klien bisnis', 'client bisnis',
        'urusan bisnis', 'keperluan bisnis', 'perjalanan bisnis', 'kerja training',
        'meeting kerja', 'meeting kerjaan', 'rapat kerja', 'meeting klien', 'ngopi meeting',
        'kopi meeting', 'konsumsi meeting', 'starbucks meeting', 'makan meeting',
        // Transport tujuan bisnis (lokal & jarak jauh)
        'untuk bisnis', 'tujuan bisnis', 'ke bisnis', 'buat bisnis', 'keperluan usaha',
        'ke klien', 'ke client', 'ke meeting', 'ke rapat', 'rapat klien', 'rapat client',
        'ketemu calon klien', 'pitch client', 'pitch klien', 'investor meeting',
        'perjalanan dinas', 'dinas luar', 'acara bisnis', 'event bisnis',
    ],
    'transport_flexible_keywords' => [
        'gym', 'nongkrong', 'hangout', 'hang out', 'healing', 'cafe', 'kafe', 'mall',
        'bioskop', 'konser', 'liburan', 'staycation', 'wisata', 'tour', 'fitness',
    ],
    // Catatan: jangan map makan+meeting → Essential; gunakan future_building_context_keywords.
    'essential_categories' => [
        'makanan & minuman', 'tempat tinggal', 'transportasi', 'komunikasi',
        'kesehatan & kebersihan diri', 'pendidikan', 'cicilan & hutang', 'pakaian & aksesoris',
    ],
    'bot_fallback_category' => 'Lain-lain',
    'bot_fallback_sub' => '-',
    'source_of_truth_note' => [
        'Taxonomy tertutup (YFD AI Taxonomy v1.3): AI HANYA memilih dari 17 kategori resmi (+ kategori pemasukan).',
        'AI tidak boleh membuat kategori baru. Jika ragu → Lain-lain (< 2%).',
        'Layer 1 = Kategori (closed list). Layer 2 = Bucket (otomatis dari mapping sistem).',
        'AI tidak menentukan bucket. Bucket dihitung dari sub-konteks + Need/Wants.',
        'Gym/olahraga berbayar → Lifestyle & Hiburan / Flexible + Social / Wants (bukan Essential).',
        'Grab/ojek ke gym → Transportasi / Flexible + Social / Wants (bukan Lifestyle).',
        'Laundry → Kesehatan & Kebersihan Diri / Essential. Fashion → Pakaian & Aksesoris.',
        'Pengembangan diri / iuran organisasi → Pendidikan / Future Building (Need atau Wants, bucket sama).',
        'Konsumsi meeting kerja / Starbucks meeting → Bisnis & Karir / Future Building (bukan Essential Living).',
        'Transportasi: bucket mengikuti tujuan (wajib → Essential; lifestyle → Flexible; bisnis/networking → Future Building).',
        'Donasi/sedekah/zakat/qurban/hadiah → Sosial & Keluarga atau Hadiah / Flexible + Social.',
        'Jenis transaksi dan bucket berbeda: biaya bisnis tetap Pengeluaran, bucket Future Building.',
        'Likuiditas sosial (Piutang + Utang Masuk/Keluar) & Kewajiban Pajak di luar 4-bucket prescription.',
    ],
];
