<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CpDigitalProduct extends Model
{
    protected $table = 'cp_digital_products';

    protected $fillable = [
        'code',
        'name',
        'tagline',
        'description',
        'icon',
        'image_url',
        'badge',
        'is_active',
        'is_featured',
        'sort',
        'price',
        'discount_price',
        'currency',
        'period',
        'features',
        'billing_mode',
        'cta_label',
        'cta_url',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'features'       => 'array',
        'is_active'      => 'boolean',
        'is_featured'    => 'boolean',
        'price'          => 'integer',
        'discount_price' => 'integer',
        'sort'           => 'integer',
    ];

    protected $appends = ['effective_price', 'on_sale', 'discount_percent'];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    /**
     * Harga efektif yang akan ditagihkan: discount_price kalau ada & < price, kalau tidak = price.
     */
    public function getEffectivePriceAttribute(): int
    {
        if ($this->discount_price !== null && $this->discount_price > 0 && $this->discount_price < $this->price) {
            return (int) $this->discount_price;
        }
        return (int) $this->price;
    }

    public function getOnSaleAttribute(): bool
    {
        return $this->discount_price !== null
            && $this->discount_price > 0
            && $this->discount_price < $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (! $this->on_sale || ! $this->price) {
            return 0;
        }
        return (int) round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'digital_product_id');
    }

    /**
     * Format helper buat blade.
     */
    public function priceLabel(?int $value = null): string
    {
        $value = $value ?? $this->effective_price;
        return ($this->currency ?? 'IDR') === 'IDR'
            ? 'Rp ' . number_format($value, 0, ',', '.')
            : number_format($value, 0, ',', '.') . ' ' . $this->currency;
    }
}
