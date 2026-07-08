<?php

/**
 * Playbook tahap keuangan (FMR) — sumber konten interpretasi baseline portal YFD.
 * Format selaras template manual Your Financial Doctor (FMR).
 */
return [
    'surviving' => [
        'diagnosis' => 'Financial Emergency Stage',
        'summary' => 'Kamu berada di fase darurat finansial: pemasukan dan pengeluaran masih sulit dijaga, dan risiko tertekan cukup tinggi. Prioritas sekarang bukan investasi atau gaya hidup — melainkan bertahan, menghentikan kebocoran, dan memulihkan cash flow sedikit demi sedikit.',
        'therapy_plan' => [
            'Stop dulu pengeluaran non-esensial sampai cash flow minimal tidak negatif 1–2 bulan berturut-turut.',
            'Catat setiap transaksi (bot YFD / spreadsheet) — tanpa visibility, sulit sembuh.',
            'Prioritaskan kebutuhan dasar: makan, transport, tagihan wajib, dan utang yang berbunga tinggi.',
            'Mulai buffer darurat kecil (target awal: 1× pengeluaran bulanan), meski nominalnya masih sederhana.',
        ],
        'bridge' => 'Empat langkah ini membawa dari kondisi darurat menuju fase pemulihan (Growing) — satu langkah stabil pada satu waktu.',
        'targets' => [
            '3m' => 'Tidak ada bulan dengan pengeluaran > pemasukan; semua transaksi tercatat; utang konsumtif tidak bertambah.',
            '12m' => 'Cash flow positif konsisten, dana darurat ≥1–3× pengeluaran bulanan, dan utang konsumtif menurun atau terstruktur.',
        ],
        'doctor_notes' => [
            'Di fase ini, “bertahan tanpa makin jatuh” sudah merupakan kemajuan — jangan bandingkan diri dengan orang di fase Steady.',
            'Investasi dan passive income belum jadi prioritas; fokus pada stabilitas dan kebiasaan dasar.',
            'Jika tekanan keuangan mengganggu tidur atau relasi, pertimbangkan bantuan profesional — itu bagian dari perawatan, bukan kegagalan.',
        ],
    ],

    'growing' => [
        'diagnosis' => 'Financial Recovery & Structuring Stage',
        'summary' => 'Kamu sudah keluar dari zona paling rawan, tetapi struktur keuangan masih rapuh terhadap guncangan besar. Fase ini tentang membangun kebiasaan: surplus bulanan, proteksi dasar, dana darurat yang memadai, dan investasi sederhana yang terencana — bukan spekulatif.',
        'therapy_plan' => [
            'Jaga surplus bulanan dan alokasikan minimal 10–20% pendapatan untuk tabungan/investasi rutin.',
            'Lengkapi proteksi dasar: BPJS dan/atau asuransi kesehatan; evaluasi utang dan cicilan produktif vs konsumtif.',
            'Naikkan dana darurat bertahap menuju 3–6× pengeluaran bulanan.',
            'Mulai investasi sederhana sesuai profil risiko (reksadana pasar uang/pendapatan tetap) — konsisten lebih penting dari nominal besar.',
        ],
        'bridge' => 'Disiplin di fase Growing menjadi fondasi untuk masuk fase Steady — stabilitas jangka menengah.',
        'targets' => [
            '3m' => 'Budget 4-bucket YFD First Aid aktif; saving rate ≥10%; proteksi kesehatan terisi di baseline.',
            '12m' => 'Dana darurat ≥3× pengeluaran; rencana pensiun/investasi tertulis; skor diagnostik naik menuju Steady.',
        ],
        'doctor_notes' => [
            'Jangan terburu-buru “ngejar return” sebelum fondasi proteksi dan dana darurat kuat.',
            'Konsistensi kecil setiap bulan mengalahkan aksi besar sekali-kali lalu hilang lagi.',
            'Evaluasi ulang baseline setiap 6 bulan — tahap keuangan bisa berubah seiring kebiasaan baru.',
        ],
    ],

    'steady' => [
        'diagnosis' => 'Financial Stable and Accumulation Stage',
        'summary' => 'Secara pondasi keuanganmu sudah cukup solid: cash flow relatif sehat, fondasi proteksi dan tabungan sudah terbentuk. Kamu punya kapasitas untuk fokus pada akumulasi — memperbesar investasi, mengoptimalkan aset, dan membangun passive income sebagai jembatan menuju financial freedom.',
        'therapy_plan' => [
            'Pertahankan dana darurat — GOOD JOB! Jangan menggerogoti untuk lifestyle upgrade impulsif.',
            'Perbesar investasi “leher ke atas”: diversifikasi instrumen, kontrol risiko, dan review portofolio berkala.',
            'Optimasi pajak & struktur aset — tingkatkan efisiensi tanpa mengorbankan proteksi.',
            'Bangun passive income (bunga, dividen, sewa, royalti) sebagai mesin kebebasan finansial jangka panjang.',
        ],
        'bridge' => 'Keempat langkah ini adalah jembatan dari Financial Stability menuju Financial Freedom.',
        'targets' => [
            '3m' => 'Paham money personality & profil risiko; alokasi investasi selaras tujuan; bucket Future Building ≥30% dari income.',
            '12m' => 'On track optimalisasi tujuan keuangan jangka panjang; passive income mulai terukur; skor diagnostik stabil atau naik ke Comfortable.',
        ],
        'doctor_notes' => [
            'Tujuan kita bukan kaya cepat, tapi sehat finansial dan tahan krisis — konsistensi mengalahkan euforia.',
            'Pondasi sudah solid; sekarang saatnya perencanaan terstruktur agar tujuan keuangan bukan hanya mimpi.',
            'Pertimbangkan konsultasi lanjutan YFD untuk framework tujuan, profil risiko, money personality, dan pola financial dysregulation.',
        ],
    ],

    'comfortable' => [
        'diagnosis' => 'Financial Freedom and Stewardship Stage',
        'summary' => 'Kondisi keuanganmu kuat: pemasukan relatif mandiri, aset dan investasi berkembang, dan risiko hidup sehari-hari sudah terkelola dengan baik. Fokus bergeser dari “mengejar cukup” menjadi preservasi kekayaan, legacy, dan penggunaan uang yang selaras dengan nilai hidup jangka panjang.',
        'therapy_plan' => [
            'Pertahankan diversifikasi aset dan governance risiko portofolio — hindari konsentrasi berlebihan.',
            'Optimalkan struktur keuangan keluarga: estate planning, proteksi jiwa/kesehatan, dan efisiensi pajak.',
            'Perkuat dan dokumentasikan aliran passive income agar tidak bergantung pada aktivitas aktif semata.',
            'Arahkan sebagian kekayaan untuk dampak positif (keluarga, komunitas, tujuan bermakna) — stewardship, bukan hanya akumulasi.',
        ],
        'bridge' => 'Dari financial freedom menuju stewardship: mengelola kekayaan dengan bijak, berkelanjutan, dan bermakna.',
        'targets' => [
            '3m' => 'Review menyeluruh portofolio, proteksi, dan alignment dengan tujuan hidup; update baseline & goal planner.',
            '12m' => 'Rencana legacy/charitable tertulis; cash flow mandiri terukur; mentoring atau sharing literasi keuangan (opsional).',
        ],
        'doctor_notes' => [
            'Financial freedom bukan akhir perjalanan — regulasi emosi dan disiplin tetap penting agar tidak complacent.',
            'Kekayaan paling bermakna ketika mendukung kehidupan yang kamu pilih, bukan sekadar angka di rekening.',
            'Pertimbangkan pendamping profesional untuk perencanaan warisan, pajak, dan struktur aset lintas generasi.',
        ],
    ],
];
