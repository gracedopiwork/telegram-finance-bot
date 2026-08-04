<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpPartner extends Model
{
    protected $table = 'cp_partners';

    protected $fillable = [
        'title', 'icon', 'description',
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
