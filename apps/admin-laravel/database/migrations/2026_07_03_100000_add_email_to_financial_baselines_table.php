<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_baselines')) {
            return;
        }

        if (! Schema::hasColumn('financial_baselines', 'email')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->string('email', 255)->nullable()->after('telegram_user_id');
                $table->index('email');
            });
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE financial_baselines MODIFY telegram_user_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE financial_baselines ALTER COLUMN telegram_user_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('financial_baselines')) {
            return;
        }

        if (Schema::hasColumn('financial_baselines', 'email')) {
            Schema::table('financial_baselines', function (Blueprint $table) {
                $table->dropIndex(['email']);
                $table->dropColumn('email');
            });
        }
    }
};
