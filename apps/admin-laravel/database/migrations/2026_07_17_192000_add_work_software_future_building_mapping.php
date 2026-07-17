<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        $exists = DB::table('category_bucket_mappings')
            ->where('category', 'Subscription')
            ->where('bucket', 'Future Building')
            ->where('reason', 'Software/langganan untuk pekerjaan & produktivitas')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('category_bucket_mappings')->insert([
            'category' => 'Subscription',
            'sub_category' => '-',
            'bucket' => 'Future Building',
            'transaction_type' => 'expense',
            'nature' => 'Need',
            'match_keywords' => 'capcut,capcut pro,canva pro,adobe,software kerja,untuk kerja,kebutuhan kerja,edit video,video editing,desain kerja,konten kerja,proyek,project,bisnis,usaha',
            'reason' => 'Software/langganan untuk pekerjaan & produktivitas',
            'sort_order' => 26,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        DB::table('category_bucket_mappings')
            ->where('category', 'Subscription')
            ->where('reason', 'Software/langganan untuk pekerjaan & produktivitas')
            ->delete();
    }
};
