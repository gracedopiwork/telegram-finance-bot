<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tracker per orang: tujuan, jatuh tempo utang, flag notifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_social_receivables')) {
            Schema::table('bot_social_receivables', function (Blueprint $table): void {
                if (! Schema::hasColumn('bot_social_receivables', 'purpose')) {
                    $table->string('purpose', 180)->default('')->after('counterparty_name');
                }
                if (! Schema::hasColumn('bot_social_receivables', 'due_notified_at')) {
                    $table->timestamp('due_notified_at')->nullable()->after('expected_back_at');
                }
            });
        }

        if (Schema::hasTable('bot_social_payables')) {
            Schema::table('bot_social_payables', function (Blueprint $table): void {
                if (! Schema::hasColumn('bot_social_payables', 'purpose')) {
                    $table->string('purpose', 180)->default('')->after('counterparty_name');
                }
                if (! Schema::hasColumn('bot_social_payables', 'expected_back_at')) {
                    $table->timestamp('expected_back_at')->nullable()->after('amount');
                }
                if (! Schema::hasColumn('bot_social_payables', 'due_notified_at')) {
                    $table->timestamp('due_notified_at')->nullable()->after('expected_back_at');
                }
            });
        }

        // Backfill jatuh tempo default untuk piutang/utang aktif tanpa tanggal.
        if (Schema::hasTable('bot_social_receivables')) {
            $rows = DB::table('bot_social_receivables')
                ->where('status', 'active')
                ->whereNull('expected_back_at')
                ->get(['id', 'amount', 'created_at']);
            foreach ($rows as $row) {
                $days = $this->defaultDueDays((int) $row->amount);
                $base = $row->created_at ? \Carbon\Carbon::parse($row->created_at) : now();
                DB::table('bot_social_receivables')->where('id', $row->id)->update([
                    'expected_back_at' => $base->copy()->addDays($days),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('bot_social_payables') && Schema::hasColumn('bot_social_payables', 'expected_back_at')) {
            $rows = DB::table('bot_social_payables')
                ->where('status', 'active')
                ->whereNull('expected_back_at')
                ->get(['id', 'amount', 'created_at']);
            foreach ($rows as $row) {
                $days = $this->defaultDueDays((int) $row->amount);
                $base = $row->created_at ? \Carbon\Carbon::parse($row->created_at) : now();
                DB::table('bot_social_payables')->where('id', $row->id)->update([
                    'expected_back_at' => $base->copy()->addDays($days),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bot_social_receivables')) {
            Schema::table('bot_social_receivables', function (Blueprint $table): void {
                if (Schema::hasColumn('bot_social_receivables', 'due_notified_at')) {
                    $table->dropColumn('due_notified_at');
                }
                if (Schema::hasColumn('bot_social_receivables', 'purpose')) {
                    $table->dropColumn('purpose');
                }
            });
        }

        if (Schema::hasTable('bot_social_payables')) {
            Schema::table('bot_social_payables', function (Blueprint $table): void {
                foreach (['due_notified_at', 'expected_back_at', 'purpose'] as $col) {
                    if (Schema::hasColumn('bot_social_payables', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    private function defaultDueDays(int $amount): int
    {
        if ($amount < 500_000) {
            return 30;
        }
        if ($amount <= 2_000_000) {
            return 60;
        }

        return 90;
    }
};
