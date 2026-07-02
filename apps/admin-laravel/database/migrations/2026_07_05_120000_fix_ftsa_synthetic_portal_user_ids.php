<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('licenses')) {
            return;
        }

        $base = max(1_000_000_000_000, (int) config('portal.synthetic_user_id_base', 9_000_000_000_000));

        $rows = DB::table('licenses')
            ->where('assigned_user_id', '<', 0)
            ->get(['id']);

        foreach ($rows as $row) {
            DB::table('licenses')
                ->where('id', $row->id)
                ->update(['assigned_user_id' => $base + (int) $row->id]);
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan ID sintetis negatif.
    }
};
