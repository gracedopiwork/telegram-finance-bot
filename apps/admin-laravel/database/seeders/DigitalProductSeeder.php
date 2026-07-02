<?php

namespace Database\Seeders;

use App\Models\CpDigitalProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DigitalProductSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $products = [
            [
                'code'           => 'yfd-bot-telegram',
                'name'           => 'YFD Bot Telegram',
                'tagline'        => 'Catat keuangan harian via chat — AI auto-parse ke dashboard web YFD.',
                'description'    => "YFD Bot Telegram adalah asisten keuangan pribadi berbasis chat. Tinggal kirim pesan biasa seperti \"makan malam 50rb\" atau \"beli kopi 18000 karena ngantuk\", AI YFD otomatis mengekstrak nominal, kategori, jenis transaksi, sifat (Need/Wants/Saving/Donation), mood, dan bahkan menandai pembelian impulsif. Semua tersimpan di dashboard web pribadi yang siap dianalisis dokter finansial Anda.",
                'icon'           => 'send',
                'badge'          => 'Tersedia',
                'is_active'      => true,
                'is_featured'    => true,
                'sort'           => 1,
                'price'          => 299000,
                'discount_price' => 199000,
                'currency'       => 'IDR',
                'period'         => 'per tahun',
                'features'       => [
                    'AI parser bahasa alami (Gemini)',
                    'Klasifikasi otomatis 7 dimensi finansial',
                    'Dashboard web real-time (portal YFD)',
                    'Sistem lisensi & akun terisolasi (privat)',
                    'Update gratis selama masa langganan',
                    'Onboarding 1×24 jam oleh tim YFD',
                    'Akses grup komunitas pengguna',
                ],
                'billing_mode'   => 'midtrans',
                'cta_label'      => 'Beli Sekarang',
                'meta_title'     => 'YFD Bot Telegram — Catat Keuangan via Chat',
                'meta_description' => 'Catat keuangan harian via chat di Telegram. AI YFD otomatis klasifikasikan & simpan ke dashboard web YFD Anda.',
            ],
            [
                'code'           => 'yfd-ftsa-premium',
                'name'           => 'FTSA Premium Unlock',
                'tagline'        => 'Unlock diagnostik FTSA-32 untuk analisis behavioral yang lebih dalam.',
                'description'    => 'Produk add-on untuk membuka akses kuesioner FTSA 1–32 di portal YFD. Setelah pembayaran sukses, fitur FTSA otomatis terbuka pada akun lisensi aktif Anda.',
                'icon'           => 'psychology',
                'badge'          => 'Tersedia',
                'is_active'      => true,
                'is_featured'    => false,
                'sort'           => 5,
                'price'          => 50000,
                'discount_price' => null,
                'currency'       => 'IDR',
                'period'         => 'sekali bayar',
                'features'       => [
                    'Akses penuh kuesioner FTSA 1–32 di portal',
                    'Skoring otomatis CHD, RVD, SSD, ESD',
                    'Archetype trauma finansial personal',
                    'Insight behavioral lanjutan di dashboard',
                    'Unlock permanen untuk lisensi aktif',
                ],
                'billing_mode'   => 'midtrans',
                'cta_label'      => 'Unlock FTSA',
                'meta_title'     => 'FTSA Premium Unlock — YFD',
                'meta_description' => 'Unlock FTSA 1–32 dan dapatkan diagnosis behavioral finansial yang lebih personal.',
            ],
            [
                'code'           => 'yfd-mobile-app',
                'name'           => 'YFD Mobile App',
                'tagline'        => 'Aplikasi Android & iOS — dashboard visual, target tabungan, alert otomatis.',
                'description'    => 'Aplikasi mobile YFD untuk dashboard visual finansial. Cek health score, monitoring goal, set reminder, dan dapat insight personal — semua di telepon Anda.',
                'icon'           => 'phone_iphone',
                'badge'          => 'Coming Soon',
                'is_active'      => true,
                'is_featured'    => false,
                'sort'           => 10,
                'price'          => 0,
                'discount_price' => null,
                'currency'       => 'IDR',
                'period'         => '—',
                'features'       => [
                    'Dashboard visual cashflow & net worth',
                    'Goal tracking & savings target',
                    'Reminder & alert otomatis',
                    'Sync data dari YFD Bot',
                ],
                'billing_mode'   => 'soon',
                'cta_label'      => 'Notify Saat Launch',
            ],
            [
                'code'           => 'wealthpedia-premium',
                'name'           => 'Wealthpedia Premium',
                'tagline'        => 'Course self-paced, e-book, dan webinar bulanan tentang practical & emotional finance.',
                'description'    => 'Akses penuh ke konten premium Wealthpedia: kelas online berseri, e-book, dan webinar bulanan dengan dokter finansial QWP.',
                'icon'           => 'school',
                'badge'          => 'Coming Soon',
                'is_active'      => true,
                'is_featured'    => false,
                'sort'           => 20,
                'price'          => 0,
                'discount_price' => null,
                'currency'       => 'IDR',
                'period'         => '—',
                'features'       => [
                    'Course self-paced multi-level',
                    'E-book & worksheet finansial',
                    'Webinar bulanan QWP',
                    'Komunitas eksklusif',
                ],
                'billing_mode'   => 'soon',
                'cta_label'      => 'Notify Saat Launch',
            ],
            [
                'code'           => 'calculator-tools',
                'name'           => 'Calculator Tools',
                'tagline'        => 'Kalkulator dana darurat, KPR, pensiun, dan compound interest — gratis untuk semua.',
                'description'    => 'Kumpulan kalkulator finansial gratis untuk perencanaan keuangan harian. Tidak perlu daftar.',
                'icon'           => 'calculate',
                'badge'          => 'Coming Soon',
                'is_active'      => true,
                'is_featured'    => false,
                'sort'           => 30,
                'price'          => 0,
                'discount_price' => null,
                'currency'       => 'IDR',
                'period'         => 'gratis',
                'features'       => [
                    'Dana Darurat',
                    'KPR / Cicilan',
                    'Dana Pensiun',
                    'Compound Interest',
                ],
                'billing_mode'   => 'soon',
                'cta_label'      => 'Notify Saat Launch',
            ],
        ];

        foreach ($products as $row) {
            CpDigitalProduct::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
