<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = [
        'license_id',
        'email',
        'name',
        'referral_code',
        'npwp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(AffiliateClaim::class);
    }

    /** Order yang memakai kode referral affiliate ini (orang yang masuk lewat dia). */
    public function referredOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'affiliate_id');
    }

    public function availableBalance(): int
    {
        return (int) $this->commissions()->where('status', 'available')->sum('amount');
    }
}
