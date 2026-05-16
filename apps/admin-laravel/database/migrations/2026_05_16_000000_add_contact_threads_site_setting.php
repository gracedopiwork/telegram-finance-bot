<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('site_settings')->where('key', 'contact.threads')->exists()) {
            DB::table('site_settings')->insert([
                'key' => 'contact.threads',
                'value' => 'your_financial_doctor',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Threads (tanpa @)',
                'sort' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('site_settings')
            ->where('key', 'contact.address')
            ->update(['sort' => 8, 'updated_at' => now()]);

        Cache::forget('site_settings.all');
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'contact.threads')->delete();

        DB::table('site_settings')
            ->where('key', 'contact.address')
            ->update(['sort' => 7, 'updated_at' => now()]);

        Cache::forget('site_settings.all');
    }
};
