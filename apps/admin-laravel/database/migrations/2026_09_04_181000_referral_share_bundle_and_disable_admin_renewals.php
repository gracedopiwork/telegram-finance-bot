<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cp_digital_products')) {
            DB::table('cp_digital_products')
                ->whereIn('code', ['yfd-bot-admin-monthly', 'yfd-bot-admin-yearly'])
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);
        }

        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $shareCode = 'yfd-first-aid-ftsa';
        $eligiblePreferred = ['yfd-first-aid-ftsa', 'yfd-bot-telegram'];

        $eligibleRow = DB::table('site_settings')->where('key', 'affiliate.eligible_product_codes')->first();
        if ($eligibleRow === null) {
            DB::table('site_settings')->insert([
                'key' => 'affiliate.eligible_product_codes',
                'value' => implode(',', $eligiblePreferred),
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
                explode(',', (string) $eligibleRow->value)
            )));
            $merged = array_values(array_unique(array_merge($eligiblePreferred, $existing)));
            DB::table('site_settings')->where('key', 'affiliate.eligible_product_codes')->update([
                'value' => implode(',', $merged),
                'updated_at' => now(),
            ]);
        }

        $shareRow = DB::table('site_settings')->where('key', 'affiliate.share_product_code')->first();
        if ($shareRow === null) {
            DB::table('site_settings')->insert([
                'key' => 'affiliate.share_product_code',
                'value' => $shareCode,
                'type' => 'text',
                'group' => 'affiliate',
                'label' => 'Produk default link referral',
                'sort' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('site_settings')->where('key', 'affiliate.share_product_code')->update([
                'value' => $shareCode,
                'updated_at' => now(),
            ]);
        }

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.affiliate');
    }

    public function down(): void
    {
        if (Schema::hasTable('cp_digital_products')) {
            DB::table('cp_digital_products')
                ->whereIn('code', ['yfd-bot-admin-monthly', 'yfd-bot-admin-yearly'])
                ->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        }
    }
};
