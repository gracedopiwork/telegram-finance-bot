<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtsaCourseRegistrant extends Model
{
    protected $fillable = [
        'ftsa_course_id',
        'full_name',
        'email',
        'phone',
        'registered_at',
        'access_ends_at',
        'order_id',
        'license_id',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'access_ends_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(FtsaCourse::class, 'ftsa_course_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function accessIsActive(?\Carbon\Carbon $now = null): bool
    {
        $now ??= now();

        return $this->access_ends_at !== null && $this->access_ends_at->isFuture();
    }
}
