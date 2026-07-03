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
                'name'           => 'YFD First Aid',
                'tagline'        => 'Catat keuangan harian via chat — lisensi YFD First Aid & dashboard selamanya.',
                'description'    => "YFD First Aid adalah asisten keuangan pribadi berbasis chat Telegram. Tinggal kirim pesan biasa seperti \"makan malam 50rb\" atau \"beli kopi 18000 karena ngantuk\", AI YFD otomatis mengekstrak nominal, kategori, jenis transaksi, sifat (Need/Wants/Saving/Donation), mood, dan menandai pembelian impulsif. Semua tersimpan di dashboard web pribadi. Sekali bayar — akses bot & dashboard berlaku selamanya.",
                'icon'           => 'send',
                'badge'          => 'Tersedia',
                'is_active'      => true,
                'is_featured'    => true,
                'sort'           => 1,
                'price'          => 299000,
                'discount_price' => 199000,
                'currency'       => 'IDR',
                'period'         => 'selamanya',
                'features'       => [
                    'AI parser bahasa alami (Gemini)',
                    'Klasifikasi otomatis 7 dimensi finansial',
                    'Dashboard web real-time (portal YFD)',
                    'Sistem lisensi & akun terisolasi (privat)',
                    'Akses bot & dashboard selamanya (sekali bayar)',
                    'Onboarding 1×24 jam oleh tim YFD',
                    'Akses grup komunitas pengguna',
                ],
                'billing_mode'   => 'midtrans',
                'cta_label'      => 'Beli Sekarang',
                'meta_title'     => 'YFD First Aid — Catat Keuangan via Chat',
                'meta_description' => 'Catat keuangan harian via chat di Telegram. Lisensi YFD First Aid & dashboard berlaku selamanya — sekali bayar.',
            ],
            [
                'code'           => 'yfd-ftsa-premium',
                'name'           => 'FTSA Premium Unlock',
                'tagline'        => 'Unlock FTSA 1–32 untuk analisis behavioral — masa aktif 12 bulan evaluasi.',
                'description'    => 'Add-on untuk membuka dashboard FTSA di portal YFD: kuesioner FTSA 1–32, behavioral insight, dan indeks kesehatan finansial selama 12 bulan evaluasi. Tidak termasuk YFD First Aid (bot Telegram) atau dashboard transaksi harian.',
                'icon'           => 'psychology',
                'badge'          => 'Tersedia',
                'is_active'      => true,
                'is_featured'    => false,
                'sort'           => 5,
                'price'          => 50000,
                'discount_price' => null,
                'currency'       => 'IDR',
                'period'         => '12 bulan evaluasi',
                'features'       => [
                    'Dashboard FTSA & behavioral di portal web',
                    'Kuesioner FTSA 1–32 lengkap',
                    'Skoring otomatis CHD, RVD, SSD, ESD',
                    'Archetype trauma finansial personal',
                    'Masa aktif 12 bulan evaluasi sejak pembayaran',
                ],
                'billing_mode'   => 'midtrans',
                'cta_label'      => 'Unlock FTSA',
                'meta_title'     => 'FTSA Premium Unlock — YFD',
                'meta_description' => 'Unlock FTSA 1–32 selama 12 bulan evaluasi. Diagnosis behavioral finansial yang lebih personal di portal YFD.',
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
                    'Sync data dari YFD First Aid',
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
