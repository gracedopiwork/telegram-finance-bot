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
}
