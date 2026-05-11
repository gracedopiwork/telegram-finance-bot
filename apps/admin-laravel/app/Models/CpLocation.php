<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpLocation extends Model
{
    protected $table = 'cp_locations';

    protected $fillable = [
        'title', 'badge', 'address', 'hours',
        'image_path', 'maps_url', 'sort', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        if (str_starts_with($this->image_path, 'http')) return $this->image_path;
        return asset('storage/'.$this->image_path);
    }
}
