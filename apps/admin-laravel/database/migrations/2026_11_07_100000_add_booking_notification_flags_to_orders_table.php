<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda kirim-sekali untuk notifikasi booking konsultasi lunas (dikirim via queue).
 * Schema saja, tanpa backfill: order lama tetap null dan tidak pernah dikirimi ulang
 * karena notifikasi hanya dipicu saat order berubah menjadi paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('admin_notified_at')->nullable()->after('purchase_delivery_sent_at');
            $table->timestamp('purchase_delivery_queued_at')->nullable()->after('admin_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['admin_notified_at', 'purchase_delivery_queued_at']);
        });
    }
};
