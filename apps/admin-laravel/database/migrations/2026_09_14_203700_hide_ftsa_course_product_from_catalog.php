<?php

use App\Models\CpDigitalProduct;
use Illuminate\Database\Migrations\Migration;

/**
 * Sembunyikan kartu "FTSA Course / Perusahaan" dari halaman produk & menu.
 * Produk tetap ada di DB agar daftar via kode perusahaan (/course/ftsa/daftar) tetap jalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        CpDigitalProduct::query()
            ->where('code', 'yfd-ftsa-course')
            ->update([
                'is_active' => false,
                'is_featured' => false,
            ]);
    }

    public function down(): void
    {
        CpDigitalProduct::query()
            ->where('code', 'yfd-ftsa-course')
            ->update(['is_active' => true]);
    }
};
