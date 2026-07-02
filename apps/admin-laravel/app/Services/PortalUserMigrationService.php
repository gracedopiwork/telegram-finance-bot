<?php

namespace App\Services;

use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use Illuminate\Support\Facades\DB;

class PortalUserMigrationService
{
    /**
     * Pindahkan data portal (baseline, transaksi, dll.) dari ID sintetis FTSA ke Telegram user asli.
     */
    public function migrateSyntheticUserToTelegram(int $fromUserId, int $toUserId, string $email): void
    {
        if ($fromUserId <= 0 || $toUserId <= 0 || $fromUserId === $toUserId) {
            return;
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        DB::transaction(function () use ($fromUserId, $toUserId, $email): void {
            FinancialBaseline::query()
                ->where('telegram_user_id', $fromUserId)
                ->update(['telegram_user_id' => $toUserId]);

            FinancialBaseline::query()
                ->whereNull('telegram_user_id')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->update(['telegram_user_id' => $toUserId]);

            BotTransaction::query()
                ->where('telegram_user_id', $fromUserId)
                ->update(['telegram_user_id' => $toUserId]);

            if (DB::getSchemaBuilder()->hasTable('user_sheets')) {
                DB::table('user_sheets')
                    ->where('telegram_user_id', $fromUserId)
                    ->update(['telegram_user_id' => $toUserId]);
            }

            if (DB::getSchemaBuilder()->hasTable('user_ai_usage')) {
                DB::table('user_ai_usage')
                    ->where('telegram_user_id', $fromUserId)
                    ->update(['telegram_user_id' => $toUserId]);
            }
        });
    }
}
