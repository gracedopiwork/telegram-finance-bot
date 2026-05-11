<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpAdvisor extends Model
{
    protected $table = 'cp_advisors';

    protected $fillable = [
        'name', 'role_label', 'badges', 'years_exp',
        'spec_short', 'spec_icon', 'spec_long', 'tag',
        'photo_path', 'sort', 'is_active',
    ];

    protected $casts = [
        'badges' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function getPhotoUrlAttribute(): string
    {
        if (!$this->photo_path) {
            return asset('images/placeholder-advisor.png');
        }
        if (str_starts_with($this->photo_path, 'http')) {
            return $this->photo_path;
        }
        return asset('storage/'.$this->photo_path);
    }
}
