<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE cp_digital_products MODIFY billing_mode ENUM('midtrans','pivot','wa','url','soon') NOT NULL DEFAULT 'pivot'");
            DB::table('cp_digital_products')
                ->where('billing_mode', 'midtrans')
                ->update(['billing_mode' => 'pivot']);

            return;
        }

        // SQLite enum → CHECK constraint; tambah nilai baru dengan rebuild kolom string.
        Schema::table('cp_digital_products', function (Blueprint $table) {
            $table->string('billing_mode_tmp', 20)->nullable();
        });

        foreach (DB::table('cp_digital_products')->select('id', 'billing_mode')->get() as $row) {
            $mode = $row->billing_mode === 'midtrans' ? 'pivot' : (string) $row->billing_mode;
            DB::table('cp_digital_products')->where('id', $row->id)->update([
                'billing_mode_tmp' => $mode !== '' ? $mode : 'pivot',
            ]);
        }

        Schema::table('cp_digital_products', function (Blueprint $table) {
            $table->dropColumn('billing_mode');
        });

        Schema::table('cp_digital_products', function (Blueprint $table) {
            $table->string('billing_mode', 20)->default('pivot');
        });

        foreach (DB::table('cp_digital_products')->select('id', 'billing_mode_tmp')->get() as $row) {
            DB::table('cp_digital_products')->where('id', $row->id)->update([
                'billing_mode' => $row->billing_mode_tmp ?: 'pivot',
            ]);
        }

        Schema::table('cp_digital_products', function (Blueprint $table) {
            $table->dropColumn('billing_mode_tmp');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return;
        }

        DB::table('cp_digital_products')
            ->where('billing_mode', 'pivot')
            ->update(['billing_mode' => 'midtrans']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE cp_digital_products MODIFY billing_mode ENUM('midtrans','wa','url','soon') NOT NULL DEFAULT 'midtrans'");
        }
    }
};
