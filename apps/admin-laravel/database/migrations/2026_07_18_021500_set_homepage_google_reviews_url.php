<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Set link Google reviews YFD agar section testimoni homepage tampil
 * (antara 6 pilar layanan dan CTA bawah).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        // Knowledge Panel / reviews deep-link shared by the YFD team.
        $url = 'https://www.google.com/search?q=Your%20Financial%20Doctor&stick=H4sIAAAAAAAAAONgU1I1qDAyTzYzSjQzTzZMS7YwNza1MqhISbNINTQzSU1OS05NtDBMXMQqGplfWqTglpmXmJecmZij4JKfXJJfBABo0kI0QQAAAA&mat=CT8GW_tbUj0c#mpd=~2034873161653880383/customers/reviews';

        $exists = DB::table('site_settings')->where('key', 'reviews.google_maps_url')->exists();
        if ($exists) {
            DB::table('site_settings')->where('key', 'reviews.google_maps_url')->update([
                'value' => $url,
                'label' => 'Link Google Reviews / Business Profile',
                'updated_at' => now(),
            ]);
        } else {
            DB::table('site_settings')->insert([
                'key' => 'reviews.google_maps_url',
                'value' => $url,
                'type' => 'text',
                'group' => 'reviews',
                'label' => 'Link Google Reviews / Business Profile',
                'sort' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Pastikan title/subtitle ada supaya section tidak kosong visual.
        $defaults = [
            ['key' => 'reviews.title', 'value' => 'Dipercaya Pasien Finansial', 'type' => 'text', 'label' => 'Judul section testimoni', 'sort' => 1],
            ['key' => 'reviews.subtitle', 'value' => 'Baca pengalaman nyata pasien di Google — screening, konsultasi, dan pendampingan YFD.', 'type' => 'textarea', 'label' => 'Subtitle testimoni', 'sort' => 2],
        ];
        foreach ($defaults as $row) {
            if (DB::table('site_settings')->where('key', $row['key'])->exists()) {
                continue;
            }
            DB::table('site_settings')->insert(array_merge($row, [
                'group' => 'reviews',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.reviews');
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->where('key', 'reviews.google_maps_url')->update([
            'value' => '',
            'updated_at' => now(),
        ]);
        Cache::forget('site_settings.all');
        Cache::forget('settings.group.reviews');
    }
};
