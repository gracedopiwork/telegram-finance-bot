<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleBusinessConnection extends Model
{
    protected $fillable = [
        'account_name',
        'account_label',
        'location_name',
        'location_title',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'average_rating',
        'total_review_count',
        'last_synced_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'average_rating' => 'float',
            'total_review_count' => 'integer',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    public function isConnected(): bool
    {
        return filled($this->refresh_token) || filled($this->access_token);
    }

    public function hasLocation(): bool
    {
        return filled($this->account_name) && filled($this->location_name);
    }
}
