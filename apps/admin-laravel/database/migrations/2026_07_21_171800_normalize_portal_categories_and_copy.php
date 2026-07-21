<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('category_bucket_mappings')) {
            DB::table('category_bucket_mappings')
                ->whereRaw('LOWER(TRIM(category)) = ?', ['transportasi'])
                ->update(['category' => 'Transport']);

            DB::table('category_bucket_mappings')
                ->whereRaw('LOWER(TRIM(category)) = ?', ['hiburan'])
                ->update(['bucket' => 'Flexible + Social', 'nature' => 'Wants']);
        }

        if (Schema::hasTable('bot_transactions')) {
            DB::table('bot_transactions')
                ->whereRaw('LOWER(TRIM(category)) = ?', ['transportasi'])
                ->update(['category' => 'Transport']);

            DB::table('bot_transactions')
                ->whereRaw('LOWER(TRIM(category)) = ?', ['makanan'])
                ->update(['category' => 'Makan']);

            DB::table('bot_transactions')
                ->whereRaw('LOWER(TRIM(category)) = ?', ['jajan'])
                ->whereRaw('LOWER(TRIM(type)) = ?', ['pengeluaran'])
                ->update(['category' => 'Makan']);
        }

        foreach (['portal_guidance_snapshots', 'portal_ai_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (['clinical_summary', 'doctors_note', 'payload', 'response_text'] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->where($column, 'like', '%Financial Pulse%')
                    ->update([
                        $column => DB::raw("REPLACE($column, 'Financial Pulse', 'Ringkasan keuangan')"),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Data normalization only; no destructive rollback.
    }
};

