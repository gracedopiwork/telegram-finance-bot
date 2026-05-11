<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CpArticle extends Model
{
    protected $table = 'cp_articles';

    protected $fillable = [
        'slug', 'title', 'category', 'read_time', 'views_label',
        'description', 'content_html', 'image_path',
        'sort', 'is_active',
    ];

    protected $casts = [
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

    protected static function booted(): void
    {
        static::saving(function (CpArticle $a) {
            if (empty($a->slug)) {
                $a->slug = Str::slug($a->title).'-'.Str::lower(Str::random(4));
            }
        });
    }
}
