<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $row = DB::table('site_settings')->where('key', 'affiliate.eligible_product_codes')->first();
        $required = ['yfd-bot-telegram', 'yfd-first-aid-ftsa'];

        if ($row === null) {
            DB::table('site_settings')->insert([
                'key' => 'affiliate.eligible_product_codes',
                'value' => implode(',', $required),
                'type' => 'text',
                'group' => 'affiliate',
                'label' => 'Kode produk eligible (pisah koma)',
                'sort' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $existing = array_values(array_filter(array_map(
                static fn (string $c) => strtolower(trim($c)),
                explode(',', (string) $row->value)
            )));

            $merged = array_values(array_unique(array_merge($existing, $required)));

            DB::table('site_settings')->where('key', 'affiliate.eligible_product_codes')->update([
                'value' => implode(',', $merged),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.affiliate');
    }

    public function down(): void
    {
        // keep expanded eligibility
    }
};
