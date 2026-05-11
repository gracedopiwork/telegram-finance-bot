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
}
