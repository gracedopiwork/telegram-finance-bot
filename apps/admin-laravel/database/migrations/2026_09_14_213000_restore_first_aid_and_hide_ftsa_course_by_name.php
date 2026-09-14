<?php

use App\Models\CpDigitalProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbaiki salah sasaran hide: YFD First Aid (bot) harus tetap di katalog;
 * yang disembunyikan = FTSA Course / Workshop (by code + nama).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Sembunyikan course/workshop (code resmi + nama mirip kartu publik).
        CpDigitalProduct::query()
            ->where(function ($q) {
                $q->whereIn('code', CpDigitalProduct::HIDDEN_FROM_PUBLIC_CATALOG)
                    ->orWhere('name', 'like', 'FTSA Course%')
                    ->orWhere('name', 'like', 'FTSA Workshop%');
            })
            ->where('code', '!=', 'yfd-bot-telegram')
            ->update([
                'is_active' => false,
                'is_featured' => false,
            ]);

        // 2) Pastikan bot First Aid tidak ikut nonaktif / diganti nama course.
        $bot = CpDigitalProduct::query()->where('code', 'yfd-bot-telegram')->first();
        if (! $bot) {
            return;
        }

        $restore = [
            'is_active' => true,
            'is_featured' => true,
        ];

        $name = (string) $bot->name;
        if (
            str_starts_with($name, 'FTSA Course')
            || str_starts_with($name, 'FTSA Workshop')
            || str_contains(strtolower($name), 'perusahaan')
        ) {
            $restore['name'] = 'YFD First Aid';
            $restore['icon'] = 'send';
            $restore['badge'] = $bot->billing_mode === 'soon' ? 'Coming Soon' : (trim((string) $bot->badge) ?: 'Tersedia');
        }

        DB::table('cp_digital_products')
            ->where('code', 'yfd-bot-telegram')
            ->update(array_merge($restore, ['updated_at' => now()]));
    }

    public function down(): void
    {
        //
    }
};
