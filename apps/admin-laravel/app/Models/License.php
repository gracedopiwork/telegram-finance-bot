<?php

namespace App\Models;

use App\Services\CustomerDataPurgeService;
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

            $hasOtherLicense = self::query()
                ->where('assigned_user_id', $previousUserId)
                ->where('id', '!=', $license->id)
                ->exists();

            if ($hasOtherLicense) {
                return;
            }

            app(CustomerDataPurgeService::class)->purgeFinanceDataForTelegramUserIds([$previousUserId]);
        });

        static::deleting(function (self $license): void {
            $userIds = app(CustomerDataPurgeService::class)
                ->collectTelegramUserIdsForLicenseIds([$license->id]);

            app(CustomerDataPurgeService::class)->purgeFinanceDataForTelegramUserIds($userIds);
        });
    }
}
