<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpFaq extends Model
{
    protected $table = 'cp_faqs';

    protected $fillable = [
        'category', 'question', 'answer',
        'sort', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
