<?php

namespace App\Services;

use App\Models\FinancialBaseline;
use App\Support\FinancialBaselineSchema;

class BaselineClaimService
{
    /**
     * Hubungkan baseline guest (dari landing check-up) ke akun Telegram portal.
     */
    public function claimForUser(string $email, int $telegramUserId): ?FinancialBaseline
    {
        if (! FinancialBaselineSchema::isReady() || $telegramUserId <= 0) {
            return null;
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $existing = FinancialBaseline::latestForUser($telegramUserId);
        if ($existing !== null) {
            return $existing;
        }

        $guest = FinancialBaseline::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('telegram_user_id')
            ->orderByDesc('assessed_at')
            ->first();

        if ($guest === null) {
            return null;
        }

        $guest->update(['telegram_user_id' => $telegramUserId]);

        return $guest->fresh();
    }
}
