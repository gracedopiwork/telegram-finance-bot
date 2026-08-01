<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateClaim extends Model
{
    protected $fillable = [
        'affiliate_id',
        'gross_amount',
        'tax_percent',
        'tax_amount',
        'net_amount',
        'npwp_snapshot',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'tax_percent' => 'float',
        'tax_amount' => 'integer',
        'net_amount' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'claim_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function statusBadge(): array
    {
        return match ($this->status) {
            'pending' => ['Menunggu', 'warning'],
            'approved' => ['Disetujui', 'info'],
            'paid' => ['Dibayar', 'success'],
            'rejected' => ['Ditolak', 'danger'],
            default => [strtoupper((string) $this->status), 'secondary'],
        };
    }
}
