<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSheet extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'spreadsheet_id',
        'spreadsheet_url',
        'dashboard_version',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public static function syncFromOrder(Order $order): void
    {
        $order->loadMissing('license');
        $telegramUserId = $order->license?->assigned_user_id;
        if (! $telegramUserId || ! $order->spreadsheet_id) {
            return;
        }

        static::updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            [
                'spreadsheet_id' => $order->spreadsheet_id,
                'spreadsheet_url' => $order->spreadsheet_url,
                'status' => 'active',
            ]
        );
    }
}
