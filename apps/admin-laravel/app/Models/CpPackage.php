<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpPackage extends Model
{
    protected $table = 'cp_packages';

    protected $fillable = [
        'code', 'name', 'name_en', 'tier_label', 'price', 'period',
        'description', 'features', 'variant', 'is_recommended',
        'sort', 'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
