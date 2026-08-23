<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyIncidentLog extends Model
{
    protected $fillable = [
        'first_known_at',
        'summary',
        'notes',
        'users_notified_at',
        'authority_reported_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'first_known_at' => 'datetime',
            'users_notified_at' => 'datetime',
            'authority_reported_at' => 'datetime',
        ];
    }
}
