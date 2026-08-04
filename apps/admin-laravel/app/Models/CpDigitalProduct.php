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
        'demo_video_enabled',
        'demo_video_url',
        'demo_video_description',
    ];

    protected $casts = [
        'features'             => 'array',
        'is_active'            => 'boolean',
        'is_featured'          => 'boolean',
        'demo_video_enabled'   => 'boolean',
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

    /** Label persen diskon (satu desimal bila perlu, mis. 33,4%). */
    public function discountPercentLabel(): string
    {
        if (! $this->on_sale || ! $this->price) {
            return '0';
        }

        $pct = (($this->price - $this->discount_price) / $this->price) * 100;
        $rounded = round($pct, 1);

        if (abs($rounded - round($rounded)) < 0.05) {
            return (string) (int) round($rounded);
        }

        return number_format($rounded, 1, ',', '');
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

    public function getDemoVideoEmbedUrlAttribute(): ?string
    {
        return self::toEmbedUrl($this->demo_video_url);
    }

    public function hasDemoVideo(): bool
    {
        return $this->demo_video_enabled && $this->demo_video_embed_url !== null;
    }

    public static function toEmbedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);

        if (preg_match('#youtube\.com/embed/([a-zA-Z0-9_-]{11})#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        if (preg_match('#(?:youtube\.com/watch\?(?:.*&)?v=|youtu\.be/)([a-zA-Z0-9_-]{11})#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        if (preg_match('#player\.vimeo\.com/video/(\d+)#', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        if (preg_match('#vimeo\.com/(\d+)#', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
