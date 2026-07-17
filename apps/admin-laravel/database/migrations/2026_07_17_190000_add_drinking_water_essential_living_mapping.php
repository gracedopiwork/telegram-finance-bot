<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pastikan air minum (aqua, dll.) & frasa "kebutuhan hidup" masuk Essential Living.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_bucket_mappings')) {
            return;
        }

        $exists = DB::table('category_bucket_mappings')
            ->where('bucket', 'Essential Living')
            ->where('transaction_type', 'expense')
            ->where('match_keywords', 'like', '%aqua%')
            ->exists();

        if ($exists) {
            return;
        }

        $maxSort = (int) DB::table('category_bucket_mappings')->max('sort_order');

        DB::table('category_bucket_mappings')->insert([
            'category' => '*',
            'sub_category' => '-',
            'bucket' => 'Essential Living',
            'transaction_type' => 'expense',
            'nature' => 'Need',
            'match_keywords' => 'aqua,air minum,air mineral,air galon,galon air,beli galon,le minerale,pristine,cleo,crystalin,equil,kebutuhan hidup,kebutuhan sehari,kebutuhan harian,sembako,kebutuhan pokok',
            'reason' => 'Air minum & kebutuhan hidup pokok',
            'sort_order' => max(28, $maxSort + 1),
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
            ->where('bucket', 'Essential Living')
            ->where('reason', 'Air minum & kebutuhan hidup pokok')
            ->delete();
    }
};
