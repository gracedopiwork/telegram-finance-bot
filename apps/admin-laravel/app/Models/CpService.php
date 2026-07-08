<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpService extends Model
{
    protected $table = 'cp_services';

    protected $fillable = [
        'section', 'eyebrow', 'title', 'description',
        'icon', 'image_path', 'features',
        'cta_label', 'cta_route', 'sort', 'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        if (str_starts_with($this->image_path, 'http')) return $this->image_path;
        return asset('storage/'.$this->image_path);
    }

    public function featureLabel(): string
    {
        $features = $this->features ?? [];

        return is_array($features) && isset($features['label'])
            ? (string) $features['label']
            : 'Cakupan';
    }

    /** @return list<string> */
    public function featureItems(): array
    {
        $features = $this->features ?? [];

        if (is_array($features) && isset($features['items']) && is_array($features['items'])) {
            return array_values(array_filter($features['items'], fn ($item) => is_string($item) && trim($item) !== ''));
        }

        if (! is_array($features)) {
            return [];
        }

        return array_values(array_filter($features, fn ($item) => is_string($item) && trim($item) !== ''));
    }

    public function featureFootnote(): ?string
    {
        $features = $this->features ?? [];

        if (! is_array($features) || empty($features['footnote'])) {
            return null;
        }

        return trim((string) $features['footnote']);
    }

    /** @return array{label: string, route?: string}|null */
    public function secondaryCta(): ?array
    {
        $features = $this->features ?? [];
        $cta = is_array($features) ? ($features['cta_secondary'] ?? null) : null;

        if (! is_array($cta) || empty($cta['label'])) {
            return null;
        }

        return $cta;
    }

    public function resolveCtaUrl(): ?string
    {
        if (! $this->cta_route) {
            return null;
        }

        if ($this->cta_route === '__primary_checkup__') {
            return null;
        }

        try {
            return route($this->cta_route);
        } catch (\Throwable) {
            return null;
        }
    }
}
