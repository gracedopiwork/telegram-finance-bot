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
}
