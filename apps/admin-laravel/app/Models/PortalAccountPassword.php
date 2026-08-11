<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalAccountPassword extends Model
{
    protected $fillable = [
        'email',
        'password',
        'password_set_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password_set_at' => 'datetime',
    ];
}
