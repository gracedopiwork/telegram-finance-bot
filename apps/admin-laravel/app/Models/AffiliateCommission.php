<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id',
        'order_id',
        'amount',
        'status',
        'claim_id',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(AffiliateClaim::class, 'claim_id');
    }
}
