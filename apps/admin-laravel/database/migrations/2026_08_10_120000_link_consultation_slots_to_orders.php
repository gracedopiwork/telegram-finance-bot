<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'order_kind')) {
                    $table->string('order_kind', 40)->default('digital')->after('plan');
                }
                if (! Schema::hasColumn('orders', 'consultation_slot_id')) {
                    $table->foreignId('consultation_slot_id')
                        ->nullable()
                        ->after('license_id')
                        ->constrained('consultation_slots')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('consultation_slots')) {
            Schema::table('consultation_slots', function (Blueprint $table) {
                if (! Schema::hasColumn('consultation_slots', 'order_id')) {
                    $table->foreignId('order_id')
                        ->nullable()
                        ->after('booking_code')
                        ->constrained('orders')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('consultation_slots', 'stage_key')) {
                    $table->string('stage_key', 40)->nullable()->after('financial_stage');
                }
                if (! Schema::hasColumn('consultation_slots', 'amount_due')) {
                    $table->unsignedInteger('amount_due')->nullable()->after('stage_key');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('consultation_slots')) {
            Schema::table('consultation_slots', function (Blueprint $table) {
                if (Schema::hasColumn('consultation_slots', 'order_id')) {
                    $table->dropConstrainedForeignId('order_id');
                }
                foreach (['stage_key', 'amount_due'] as $col) {
                    if (Schema::hasColumn('consultation_slots', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'consultation_slot_id')) {
                    $table->dropConstrainedForeignId('consultation_slot_id');
                }
                if (Schema::hasColumn('orders', 'order_kind')) {
                    $table->dropColumn('order_kind');
                }
            });
        }
    }
};
