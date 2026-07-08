<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('cp_packages')) {
            return;
        }

        DB::table('cp_packages')
            ->whereIn('code', ['lite', 'pro', 'ecosystem'])
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('cp_packages')) {
            return;
        }

        DB::table('cp_packages')
            ->whereIn('code', ['lite', 'pro', 'ecosystem'])
            ->update(['is_active' => true, 'updated_at' => now()]);
    }
};
