<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'full_name',
        'email',
        'phone',
        'telegram_username',
        'referral_code',
        'affiliate_id',
        'plan',
        'digital_product_id',
        'product_name',
        'amount',
        'original_price',
        'discount_amount',
        'referral_discount',
        'currency',
        'status',
        'payment_gateway',
        'payment_reference',
        'admin_note',
        'payment_url',
        'payment_token',
        'paid_at',
        'license_id',
        'purchase_delivery_sent_at',
    ];

    protected $casts = [
        'paid_at'                   => 'datetime',
        'purchase_delivery_sent_at' => 'datetime',
        'amount'                    => 'integer',
        'original_price'            => 'integer',
        'discount_amount'           => 'integer',
        'referral_discount'         => 'integer',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function digitalProduct(): BelongsTo
    {
        return $this->belongsTo(CpDigitalProduct::class, 'digital_product_id');
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderByDesc('created_at');
    }

    /**
     * Helper untuk badge status di UI.
     */
    public function statusBadge(): array
    {
        return match ($this->status) {
            'paid'    => ['Lunas',     'success'],
            'failed'  => ['Gagal',     'danger'],
            'pending' => ['Menunggu',  'warning'],
            default   => [strtoupper($this->status), 'secondary'],
        };
    }

    public function amountLabel(): string
    {
        return ($this->currency === 'IDR' || ! $this->currency)
            ? 'Rp ' . number_format($this->amount, 0, ',', '.')
            : number_format($this->amount, 0, ',', '.') . ' ' . $this->currency;
    }

    public function isAdminComplimentary(): bool
    {
        return $this->payment_gateway === 'admin'
            || str_contains(mb_strtolower((string) ($this->admin_note ?? '')), 'dibuat admin');
    }
}
