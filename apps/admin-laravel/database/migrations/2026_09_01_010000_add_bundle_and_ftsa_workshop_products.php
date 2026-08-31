<?php

use App\Models\CpDigitalProduct;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CpDigitalProduct::updateOrCreate(
            ['code' => 'yfd-first-aid-ftsa'],
            [
                'name' => 'First Aid + FTSA Bundle',
                'tagline' => 'Bundling YFD First Aid dan FTSA dalam satu paket.',
                'description' => "Satu pembelian untuk akses YFD First Aid (bot + dashboard) dan FTSA Premium.\n\nCocok untuk paket lengkap: catat transaksi harian sekaligus kenali archetype keuangan Anda.",
                'icon' => 'package_2',
                'badge' => 'Bundle',
                'is_active' => true,
                'is_featured' => true,
                'sort' => 4,
                'price' => 249000,
                'discount_price' => 229000,
                'currency' => 'IDR',
                'period' => '1 tahun First Aid + 12 bulan evaluasi FTSA',
                'features' => [
                    'Semua fitur YFD First Aid',
                    'Semua fitur FTSA Premium',
                    'Satu lisensi untuk kedua akses',
                    'Cocok untuk onboarding lengkap',
                ],
                'billing_mode' => 'pivot',
                'cta_label' => 'Beli Bundle',
                'meta_title' => 'First Aid + FTSA Bundle | YFD',
                'meta_description' => 'Bundling YFD First Aid dan FTSA dalam satu paket pembelian.',
            ]
        );

        CpDigitalProduct::updateOrCreate(
            ['code' => 'yfd-ftsa-workshop'],
            [
                'name' => 'FTSA Workshop / Korporat',
                'tagline' => 'Akses FTSA khusus workshop & kerjasama korporat (tanpa checkout publik).',
                'description' => "Produk akses FTSA untuk peserta workshop atau mitra korporat.\n\nTidak untuk dijual di checkout publik — diberikan admin lewat menu Tambah User Gratis.",
                'icon' => 'groups',
                'badge' => 'Workshop',
                'is_active' => true,
                'is_featured' => false,
                'sort' => 6,
                'price' => 0,
                'discount_price' => null,
                'currency' => 'IDR',
                'period' => '12 bulan evaluasi',
                'features' => [
                    'Akses FTSA penuh',
                    'Untuk peserta workshop / mitra korporat',
                    'Diberikan admin tanpa pembayaran',
                ],
                'billing_mode' => 'soon',
                'cta_label' => 'Via Admin',
                'meta_title' => 'FTSA Workshop | YFD',
                'meta_description' => 'Akses FTSA khusus workshop dan kerjasama korporat.',
            ]
        );
    }

    public function down(): void
    {
        CpDigitalProduct::query()
            ->whereIn('code', ['yfd-first-aid-ftsa', 'yfd-ftsa-workshop'])
            ->delete();
    }
};
