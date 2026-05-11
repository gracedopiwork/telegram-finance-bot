<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'provider',
        'event_type',
        'payload_json',
        'created_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];
}
