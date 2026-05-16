<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('site_settings')->where('key', 'hero.cta_primary_url')->exists()) {
            DB::table('site_settings')->insert([
                'key' => 'hero.cta_primary_url',
                'value' => '',
                'type' => 'text',
                'group' => 'hero',
                'label' => 'Link CTA utama (diagnosa / eksternal, kosong = halaman Paket)',
                'sort' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('site_settings')
            ->where('key', 'hero.cta_secondary')
            ->update(['sort' => 6, 'updated_at' => now()]);

        Cache::forget('site_settings.all');
        foreach (['brand', 'contact', 'hero', 'about', 'stats', 'vision', 'mission', 'values'] as $g) {
            Cache::forget("settings.group.{$g}");
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'hero.cta_primary_url')->delete();

        DB::table('site_settings')
            ->where('key', 'hero.cta_secondary')
            ->update(['sort' => 5, 'updated_at' => now()]);

        Cache::forget('site_settings.all');
        foreach (['brand', 'contact', 'hero', 'about', 'stats', 'vision', 'mission', 'values'] as $g) {
            Cache::forget("settings.group.{$g}");
        }
    }
};
