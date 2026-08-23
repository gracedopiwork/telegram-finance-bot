<?php

/**
 * User-facing privacy policy (Lapis 1 & halaman Akun).
 * Sumber: YFD First Aid Data Privacy Informed Consent, revisi 15 Agustus 2026, Bagian 15.
 */
return [
    'version' => '1.1',
    'updated_at' => '14 Agustus 2026',
    'contact_wa' => '+62 851-1122-8911',
    'title' => 'Privasi Data & Persetujuan Penggunaan YFD First Aid',
    'intro' => 'Sebelum mulai menggunakan YFD First Aid, kami ingin kamu memahami bagaimana data yang kamu masukkan digunakan di dalam sistem.',
    'sections' => [
        [
            'heading' => 'Data yang dapat diproses',
            'body' => 'Selama menggunakan YFD First Aid, kamu dapat memasukkan atau menghasilkan data seperti transaksi finansial, informasi Financial Health, pola penggunaan, konteks transaksi, mood, hasil assessment, serta data lain yang berkaitan dengan penggunaan fitur.',
        ],
        [
            'heading' => 'Tujuan penggunaan',
            'body' => 'Data digunakan untuk menjalankan fitur YFD First Aid, termasuk pencatatan transaksi, pengelompokan berdasarkan sistem YFD, dashboard, analisis Financial Health, serta fitur atau layanan terkait yang kamu gunakan.',
        ],
        [
            'heading' => 'Akses data',
            'body' => 'Data yang terkait dengan akunmu pada prinsipnya bersifat pribadi dan tidak dapat diakses oleh pengguna lain. Setiap pengguna hanya dapat mengakses data yang terkait dengan akun miliknya sendiri melalui mekanisme autentikasi dan pembatasan akses sistem.',
        ],
        [
            'heading' => 'Akses internal YFD',
            'body' => 'Data individual kamu tidak dapat diakses oleh pihak internal YFD secara bebas. Dalam kondisi tertentu, anggota tim YFD dapat meminta akses terhadap data tertentu apabila akses tersebut memang diperlukan untuk memberikan atau mendukung layanan yang kamu gunakan, misalnya konsultasi, bantuan teknis, atau penanganan masalah pada akunmu. Akses tersebut memerlukan persetujuan kamu terlebih dahulu dan hanya dilakukan sejauh diperlukan untuk tujuan tersebut.',
        ],
        [
            'heading' => 'Berapa lama data disimpan',
            'body' => 'Karena aktivasi YFD First Aid berlaku sekali seumur hidup (bukan langganan berjangka), akunmu tidak akan otomatis dihapus atau expired oleh sistem. Data kamu disimpan selama akun kamu terdaftar di sistem YFD First Aid, termasuk saat tidak aktif karena belum membayar biaya admin tahunan. Kamu bisa meminta penghapusan data kapan pun melalui WhatsApp Admin YFD di +62 851-1122-8911.',
        ],
        [
            'heading' => 'Hak kamu sebagai pengguna',
            'body' => 'Kamu berhak melihat, meminta salinan, meminta koreksi, atau meminta penghapusan datamu. Kamu berhak menarik persetujuan pemrosesan data kapan pun. Ini terpisah dari kebijakan pembayaran — penarikan persetujuan tidak mengembalikan biaya yang sudah dibayarkan. Kamu berhak tahu bahwa First Aid melakukan pengelompokan otomatis (Financial Health Bucket, kategori FTSA) berdasarkan data yang kamu masukkan.',
        ],
        [
            'heading' => 'Kalau terjadi insiden keamanan data',
            'body' => 'Kalau terjadi kebocoran data yang berdampak ke akunmu, kami akan memberitahumu secara tertulis paling lambat 3×24 jam sejak kami mengetahuinya, termasuk data apa yang terdampak dan langkah yang kami ambil.',
        ],
        [
            'heading' => 'Pengembangan & analisis',
            'body' => 'YFD dapat menggunakan data penggunaan dalam bentuk yang sesuai untuk evaluasi, pengembangan, dan peningkatan layanan. Analisis agregat bertujuan melihat pola secara keseluruhan dan tidak dimaksudkan untuk memberikan pengguna lain akses terhadap data individual.',
        ],
        [
            'heading' => 'Penting',
            'body' => 'YFD First Aid merupakan alat pencatatan, edukasi, dan pemahaman Financial Health. Hasil sistem tidak dimaksudkan sebagai jaminan hasil finansial tertentu.',
        ],
    ],
    'purchase_summary' => 'Data transaksi dan Financial Health kamu bersifat pribadi, tidak bisa dilihat pengguna lain, dan hanya diakses tim internal jika benar-benar diperlukan untuk layanan kamu dan dengan persetujuanmu.',

    /**
     * Checkbox Lapis 2 (Bagian 16) — semua wajib dicentang sebelum lanjut.
     *
     * @var list<array{id: string, label: string}>
     */
    'checkboxes' => [
        [
            'id' => 'read_understand',
            'label' => 'Saya telah membaca dan memahami informasi mengenai penggunaan dan akses data dalam YFD First Aid.',
        ],
        [
            'id' => 'use_for_features',
            'label' => 'Saya memahami bahwa data yang saya masukkan dapat digunakan untuk menjalankan fitur, dashboard, analisis, dan layanan terkait yang saya gunakan.',
        ],
        [
            'id' => 'not_for_other_users',
            'label' => 'Saya memahami bahwa data saya tidak ditujukan untuk dapat diakses oleh pengguna lain.',
        ],
        [
            'id' => 'internal_access_with_consent',
            'label' => 'Saya memahami dan menyetujui bahwa, apabila diperlukan untuk memberikan atau mendukung layanan yang saya gunakan, pihak internal YFD dapat meminta akses terhadap data saya, hanya setelah persetujuan saya dan sejauh diperlukan.',
        ],
        [
            'id' => 'rights_and_no_refund',
            'label' => 'Saya memahami hak saya untuk mengakses, mengoreksi, menghapus data, dan menarik persetujuan kapan pun — serta memahami bahwa penarikan persetujuan tidak mengembalikan biaya yang sudah saya bayarkan.',
        ],
        [
            'id' => 'agree_processing',
            'label' => 'Saya menyetujui pemrosesan data saya untuk penggunaan YFD First Aid sesuai informasi yang telah dijelaskan.',
        ],
    ],
];
