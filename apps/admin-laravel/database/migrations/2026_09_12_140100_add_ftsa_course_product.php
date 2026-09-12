<?php

use App\Models\CpDigitalProduct;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CpDigitalProduct::updateOrCreate(
            ['code' => 'yfd-ftsa-course'],
            [
                'name' => 'FTSA Course / Perusahaan',
                'tagline' => 'Akses FTSA 1 bulan via kode daftar perusahaan (workshop).',
                'description' => "Akses FTSA khusus peserta course korporat.\n\nTidak dijual di checkout publik — dibuka lewat kode daftar course. Akses 1 bulan dari tanggal workshop; data tetap tersimpan dan bisa dibuka lagi setelah beli YFD First Aid.",
                'icon' => 'school',
                'badge' => 'Course',
                'is_active' => true,
                'is_featured' => false,
                'sort' => 7,
                'price' => 0,
                'discount_price' => null,
                'currency' => 'IDR',
                'period' => '1 bulan dari tanggal workshop',
                'features' => [
                    'Daftar via kode perusahaan',
                    'Isi FTSA 1–32',
                    'Hasil langsung & terikat email',
                    'Akses course 1 bulan',
                    'Data tetap tersimpan setelah terkunci',
                    'Buka lagi setelah aktivasi YFD First Aid',
                ],
                'billing_mode' => 'soon',
                'cta_label' => 'Daftar Course',
                'meta_title' => 'FTSA Course Perusahaan | YFD',
                'meta_description' => 'Akses FTSA untuk peserta course/workshop perusahaan via kode daftar.',
            ]
        );
    }

    public function down(): void
    {
        CpDigitalProduct::query()->where('code', 'yfd-ftsa-course')->delete();
    }
};
