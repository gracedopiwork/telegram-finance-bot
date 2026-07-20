<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Slot review 4–6 untuk carousel homepage (YFD punya 4+ ulasan Google).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $rows = [];
        foreach ([4, 5, 6] as $i) {
            $base = 10 + (($i - 1) * 10);
            $rows[] = ['key' => "reviews.r{$i}.name", 'value' => '', 'type' => 'text', 'label' => "Review {$i} — Nama", 'sort' => $base];
            $rows[] = ['key' => "reviews.r{$i}.text", 'value' => '', 'type' => 'textarea', 'label' => "Review {$i} — Isi", 'sort' => $base + 1];
            $rows[] = ['key' => "reviews.r{$i}.rating", 'value' => '5', 'type' => 'text', 'label' => "Review {$i} — Rating (1-5)", 'sort' => $base + 2];
        }

        foreach ($rows as $row) {
            if (DB::table('site_settings')->where('key', $row['key'])->exists()) {
                continue;
            }
            DB::table('site_settings')->insert([
                'key' => $row['key'],
                'value' => $row['value'],
                'type' => $row['type'],
                'group' => 'reviews',
                'label' => $row['label'],
                'sort' => $row['sort'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.reviews');
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->whereIn('key', [
            'reviews.r4.name', 'reviews.r4.text', 'reviews.r4.rating',
            'reviews.r5.name', 'reviews.r5.text', 'reviews.r5.rating',
            'reviews.r6.name', 'reviews.r6.text', 'reviews.r6.rating',
        ])->delete();

        Cache::forget('site_settings.all');
        Cache::forget('settings.group.reviews');
    }
};
