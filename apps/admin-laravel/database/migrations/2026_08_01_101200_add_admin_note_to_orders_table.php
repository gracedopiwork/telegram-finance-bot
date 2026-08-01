<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('payment_reference');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'admin_note')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('admin_note');
        });
    }
};
