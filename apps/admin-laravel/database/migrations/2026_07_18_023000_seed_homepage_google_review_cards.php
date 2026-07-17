<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed kartu testimoni homepage dari ulasan Google YFD (screenshot Juli 2026).
 * Kartu tampil hanya jika name + text terisi — review 2/3 bisa dilengkapi di Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $rows = [
            ['key' => 'reviews.google_rating', 'value' => '5.0', 'type' => 'text', 'label' => 'Rating Google (contoh: 5.0)', 'sort' => 4],
            ['key' => 'reviews.google_count', 'value' => '4', 'type' => 'text', 'label' => 'Jumlah ulasan Google', 'sort' => 5],
            ['key' => 'reviews.subtitle', 'value' => 'Rating 5,0 dari 4 ulasan Google — pengalaman nyata setelah screening & konsultasi YFD.', 'type' => 'textarea', 'label' => 'Subtitle testimoni', 'sort' => 2],
            ['key' => 'reviews.r1.name', 'value' => 'K', 'type' => 'text', 'label' => 'Review 1 — Nama', 'sort' => 10],
            ['key' => 'reviews.r1.text', 'value' => 'Satisfied with the professional service from Your Financial Doctor. They helped me better understand how to manage my finances in an informative, friendly, and fun way. Highly recommended 👌', 'type' => 'textarea', 'label' => 'Review 1 — Isi', 'sort' => 11],
            ['key' => 'reviews.r1.rating', 'value' => '5', 'type' => 'text', 'label' => 'Review 1 — Rating (1-5)', 'sort' => 12],
            ['key' => 'reviews.r2.name', 'value' => 'Reinaldi Rongrean', 'type' => 'text', 'label' => 'Review 2 — Nama', 'sort' => 20],
            ['key' => 'reviews.r2.rating', 'value' => '5', 'type' => 'text', 'label' => 'Review 2 — Rating (1-5)', 'sort' => 22],
        ];

        foreach ($rows as $row) {
            $payload = [
                'value' => $row['value'],
                'type' => $row['type'],
                'group' => 'reviews',
                'label' => $row['label'],
                'sort' => $row['sort'],
                'updated_at' => now(),
            ];

            if (DB::table('site_settings')->where('key', $row['key'])->exists()) {
                DB::table('site_settings')->where('key', $row['key'])->update($payload);
            } else {
                DB::table('site_settings')->insert(array_merge($payload, [
                    'key' => $row['key'],
                    'created_at' => now(),
                ]));
            }
        }

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.reviews');
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        foreach (['reviews.r1.name', 'reviews.r1.text'] as $key) {
            DB::table('site_settings')->where('key', $key)->update([
                'value' => '',
                'updated_at' => now(),
            ]);
        }

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.reviews');
    }
};
