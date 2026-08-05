<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taxonomy v1.3 behavioral flags (Section 2.14 / 3.6 / 5C):
 * risk_alert (pinjol), late_pattern (denda berulang), life_event (peristiwa besar).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bot_transactions')) {
            return;
        }

        Schema::table('bot_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('bot_transactions', 'taxonomy_flags')) {
                $table->json('taxonomy_flags')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bot_transactions')) {
            return;
        }

        Schema::table('bot_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bot_transactions', 'taxonomy_flags')) {
                $table->dropColumn('taxonomy_flags');
            }
        });
    }
};
