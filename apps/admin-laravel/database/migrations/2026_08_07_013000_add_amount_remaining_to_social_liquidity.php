<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sisa piutang/utang untuk cicilan pelunasan sebagian.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_social_receivables') && ! Schema::hasColumn('bot_social_receivables', 'amount_remaining')) {
            Schema::table('bot_social_receivables', function (Blueprint $table): void {
                $table->unsignedBigInteger('amount_remaining')->nullable()->after('amount');
            });
            $rows = DB::table('bot_social_receivables')->get(['id', 'amount', 'status']);
            foreach ($rows as $row) {
                $remaining = $row->status === 'active' ? (int) $row->amount : 0;
                DB::table('bot_social_receivables')->where('id', $row->id)->update([
                    'amount_remaining' => $remaining,
                ]);
            }
        }

        if (Schema::hasTable('bot_social_payables') && ! Schema::hasColumn('bot_social_payables', 'amount_remaining')) {
            Schema::table('bot_social_payables', function (Blueprint $table): void {
                $table->unsignedBigInteger('amount_remaining')->nullable()->after('amount');
            });
            $rows = DB::table('bot_social_payables')->get(['id', 'amount', 'status']);
            foreach ($rows as $row) {
                $remaining = $row->status === 'active' ? (int) $row->amount : 0;
                DB::table('bot_social_payables')->where('id', $row->id)->update([
                    'amount_remaining' => $remaining,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bot_social_receivables') && Schema::hasColumn('bot_social_receivables', 'amount_remaining')) {
            Schema::table('bot_social_receivables', function (Blueprint $table): void {
                $table->dropColumn('amount_remaining');
            });
        }
        if (Schema::hasTable('bot_social_payables') && Schema::hasColumn('bot_social_payables', 'amount_remaining')) {
            Schema::table('bot_social_payables', function (Blueprint $table): void {
                $table->dropColumn('amount_remaining');
            });
        }
    }
};
