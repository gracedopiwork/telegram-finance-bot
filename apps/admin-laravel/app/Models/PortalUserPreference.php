<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalUserPreference extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'timezone',
        'timezone_source',
    ];

    public static function forUser(int $telegramUserId): ?self
    {
        if ($telegramUserId <= 0) {
            return null;
        }

        return self::query()->where('telegram_user_id', $telegramUserId)->first();
    }
}
