<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'plan',
        'status',
        'expires_at',
        'max_accounts',
        'assigned_user_id',
        'assigned_username',
        'activated_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (self $license): void {
            if (! $license->wasChanged('assigned_user_id')) {
                return;
            }

            $previousUserId = (int) ($license->getOriginal('assigned_user_id') ?? 0);
            if ($previousUserId <= 0) {
                return;
            }

            self::purgeUserFinanceDataIfOrphaned($previousUserId, $license->id);
        });

        static::deleting(function (self $license): void {
            $userId = (int) ($license->assigned_user_id ?? 0);
            if ($userId <= 0) {
                return;
            }

            self::purgeUserFinanceDataIfOrphaned($userId, $license->id);
        });
    }

    private static function purgeUserFinanceDataIfOrphaned(int $telegramUserId, int $currentLicenseId): void
    {
        $hasOtherLicense = self::query()
            ->where('assigned_user_id', $telegramUserId)
            ->where('id', '!=', $currentLicenseId)
            ->exists();

        if ($hasOtherLicense) {
            return;
        }

        BotTransaction::query()->where('telegram_user_id', $telegramUserId)->delete();
        FinancialBaseline::query()->where('telegram_user_id', $telegramUserId)->delete();
    }
}
