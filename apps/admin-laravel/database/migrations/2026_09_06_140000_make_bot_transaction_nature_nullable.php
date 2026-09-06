<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Taxonomy v1.8 §5.4: Need/Want hanya untuk Pengeluaran — nature boleh null.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE bot_transactions MODIFY nature VARCHAR(32) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE bot_transactions ALTER COLUMN nature DROP NOT NULL');

            return;
        }

        // sqlite (tests): recreate column via table rebuild is heavy; skip if already nullable.
        if ($driver === 'sqlite') {
            try {
                DB::statement('ALTER TABLE bot_transactions ALTER COLUMN nature DROP NOT NULL');
            } catch (\Throwable) {
                // SQLite < / Laravel versions may not support; leave as-is for tests.
            }
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE bot_transactions SET nature = 'Need' WHERE nature IS NULL");
            DB::statement("ALTER TABLE bot_transactions MODIFY nature VARCHAR(32) NOT NULL DEFAULT 'Need'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE bot_transactions SET nature = 'Need' WHERE nature IS NULL");
            DB::statement('ALTER TABLE bot_transactions ALTER COLUMN nature SET NOT NULL');
        }
    }
};
