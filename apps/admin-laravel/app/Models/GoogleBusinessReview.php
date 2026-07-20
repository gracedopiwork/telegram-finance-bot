<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GoogleBusinessReview extends Model
{
    protected $fillable = [
        'google_review_id',
        'reviewer_name',
        'reviewer_photo_url',
        'rating',
        'comment',
        'reviewed_at',
        'reply_comment',
        'reply_updated_at',
        'is_published',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'reviewed_at' => 'datetime',
            'reply_updated_at' => 'datetime',
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNotNull('comment')->where('comment', '!=', '');
    }

    public function scopeForHomepage(Builder $query): Builder
    {
        return $query->published()->orderByDesc('reviewed_at')->orderBy('sort');
    }
}
