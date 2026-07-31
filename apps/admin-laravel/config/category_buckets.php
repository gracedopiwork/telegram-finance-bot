<?php

/**
 * Pemetaan transaksi ke 4 bucket prescription (YFD First Aid).
 * Taxonomy tertutup — lihat config/yfd_taxonomy.php.
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
        'pengembangan diri', 'self development', 'les', 'coaching', 'mentoring',
        'chatgpt', 'claude', 'notion', 'canva pro', 'figma', 'laptop kerja',
    ],
    'flexible_keywords' => [
        'jajan', 'kopi', 'coffee', 'cafe', 'nongkrong', 'healing',
        'liburan', 'staycation', 'bioskop', 'konser', 'hobi', 'hadiah', 'donasi', 'sedekah',
        'persembahan', 'perpuluhan', 'streaming', 'gaming', 'fashion', 'skincare',
        'make up', 'fomo', 'subscription', 'langganan', 'netflix', 'spotify', 'gym', 'pilates',
    ],
    'essential_context_keywords' => [
        'hp rusak', 'handphone rusak', 'ganti hp', 'hp pecah', 'layar pecah', 'hp mati',
        'fisioterapi', 'rehab', 'rehabilitasi',
    ],
    'future_building_context_keywords' => [
        'laptop kerja', 'laptop produktif', 'alat kerja', 'untuk kerja', 'modal kerja',
        'freelancer it', 'website bisnis', 'software bisnis', 'marketing bisnis',
        'modal usaha', 'proyek bisnis', 'konten bisnis',
    ],
    'essential_meeting_keywords' => [
        'starbucks', 'meeting kerja', 'rapat kerja', 'meeting klien', 'ngopi meeting', 'kopi meeting',
    ],
    'essential_categories' => [
        'makanan & minuman', 'tempat tinggal', 'transportasi', 'komunikasi',
        'kesehatan & kebersihan diri', 'pendidikan', 'cicilan & hutang', 'pakaian & aksesoris',
    ],
    'bot_fallback_category' => 'Lain-lain',
    'bot_fallback_sub' => '-',
    'source_of_truth_note' => [
        'Taxonomy tertutup (YFD AI Taxonomy FINAL REVISED): AI HANYA memilih dari 16 kategori resmi (+ kategori pemasukan).',
        'AI tidak boleh membuat kategori baru. Jika ragu → Lain-lain.',
        'Layer 1 = Kategori (closed list). Layer 2 = Bucket (otomatis dari mapping sistem).',
        'AI tidak menentukan bucket. Bucket dihitung dari sub-konteks + Need/Wants.',
        'Gym/olahraga berbayar → Lifestyle & Hiburan / Flexible + Social / Wants (bukan Essential).',
        'Laundry → Kesehatan & Kebersihan Diri / Essential. Fashion → Pakaian & Aksesoris.',
        'Pengembangan diri → Pendidikan / Future Building (Need atau Wants, bucket sama).',
        'Donasi/sedekah/zakat/hadiah → Sosial & Keluarga atau Hadiah / Flexible + Social.',
        'Jenis transaksi dan bucket berbeda: biaya bisnis tetap Pengeluaran, bucket Future Building.',
    ],
];
