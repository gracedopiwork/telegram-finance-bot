<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $rows = [
            ['key' => 'reviews.title', 'value' => 'Dipercaya Pasien Finansial', 'type' => 'text', 'group' => 'reviews', 'label' => 'Judul section testimoni', 'sort' => 1],
            ['key' => 'reviews.subtitle', 'value' => 'Ulasan dari Google Business Profile — pengalaman nyata setelah screening & konsultasi YFD.', 'type' => 'textarea', 'group' => 'reviews', 'label' => 'Subtitle testimoni', 'sort' => 2],
            ['key' => 'reviews.google_maps_url', 'value' => '', 'type' => 'text', 'group' => 'reviews', 'label' => 'Link Google Maps / Business Profile', 'sort' => 3],
            ['key' => 'reviews.r1.name', 'value' => '', 'type' => 'text', 'group' => 'reviews', 'label' => 'Review 1 — Nama', 'sort' => 10],
            ['key' => 'reviews.r1.text', 'value' => '', 'type' => 'textarea', 'group' => 'reviews', 'label' => 'Review 1 — Isi', 'sort' => 11],
            ['key' => 'reviews.r1.rating', 'value' => '5', 'type' => 'text', 'group' => 'reviews', 'label' => 'Review 1 — Rating (1-5)', 'sort' => 12],
            ['key' => 'reviews.r2.name', 'value' => '', 'type' => 'text', 'group' => 'reviews', 'label' => 'Review 2 — Nama', 'sort' => 20],
            ['key' => 'reviews.r2.text', 'value' => '', 'type' => 'textarea', 'group' => 'reviews', 'label' => 'Review 2 — Isi', 'sort' => 21],
            ['key' => 'reviews.r2.rating', 'value' => '5', 'type' => 'text', 'group' => 'reviews', 'label' => 'Review 2 — Rating (1-5)', 'sort' => 22],
            ['key' => 'reviews.r3.name', 'value' => '', 'type' => 'text', 'group' => 'reviews', 'label' => 'Review 3 — Nama', 'sort' => 30],
            ['key' => 'reviews.r3.text', 'value' => '', 'type' => 'textarea', 'group' => 'reviews', 'label' => 'Review 3 — Isi', 'sort' => 31],
            ['key' => 'reviews.r3.rating', 'value' => '5', 'type' => 'text', 'group' => 'reviews', 'label' => 'Review 3 — Rating (1-5)', 'sort' => 32],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('settings')->where('key', $row['key'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->where('group', 'reviews')->delete();
    }
};
