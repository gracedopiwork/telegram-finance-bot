<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')
            ->where('key', 'brand.logo')
            ->update(['label' => 'Logo header (navbar)', 'updated_at' => now()]);

        if (! DB::table('site_settings')->where('key', 'brand.logo_footer')->exists()) {
            DB::table('site_settings')->insert([
                'key' => 'brand.logo_footer',
                'value' => '',
                'type' => 'image',
                'group' => 'brand',
                'label' => 'Logo footer (kosong = sama dengan header)',
                'sort' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('site_settings.all');
        foreach (['brand', 'contact', 'hero', 'about', 'stats', 'vision', 'mission', 'values'] as $g) {
            Cache::forget("settings.group.{$g}");
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'brand.logo_footer')->delete();

        DB::table('site_settings')
            ->where('key', 'brand.logo')
            ->update(['label' => 'Logo (path public/)', 'updated_at' => now()]);

        Cache::forget('site_settings.all');
        foreach (['brand', 'contact', 'hero', 'about', 'stats', 'vision', 'mission', 'values'] as $g) {
            Cache::forget("settings.group.{$g}");
        }
    }
};
