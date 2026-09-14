<?php

use App\Models\CpDigitalProduct;
use Illuminate\Database\Migrations\Migration;

/**
 * Pastikan FTSA Course tidak muncul di /produk (meski is_active sempat true lagi).
 */
return new class extends Migration
{
    public function up(): void
    {
        CpDigitalProduct::query()
            ->whereIn('code', CpDigitalProduct::HIDDEN_FROM_PUBLIC_CATALOG)
            ->update([
                'is_active' => false,
                'is_featured' => false,
            ]);
    }

    public function down(): void
    {
        //
    }
};
