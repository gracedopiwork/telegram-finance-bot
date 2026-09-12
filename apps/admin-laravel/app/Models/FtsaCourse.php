<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FtsaCourse extends Model
{
    protected $fillable = [
        'name',
        'company_name',
        'registration_code',
        'workshop_at',
        'access_days',
        'max_registrants',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'workshop_at' => 'date',
        'access_days' => 'integer',
        'max_registrants' => 'integer',
        'is_active' => 'boolean',
    ];

    public function registrants(): HasMany
    {
        return $this->hasMany(FtsaCourseRegistrant::class);
    }

    public function accessEndsAt(): Carbon
    {
        $days = max(1, (int) ($this->access_days ?: 30));

        return $this->workshop_at->copy()->startOfDay()->addDays($days)->endOfDay();
    }

    public function registrationWindowOpen(?Carbon $now = null): bool
    {
        $now ??= now();
        if (! $this->is_active) {
            return false;
        }

        return $now->lte($this->accessEndsAt());
    }

    public function seatsRemaining(): ?int
    {
        if ($this->max_registrants === null) {
            return null;
        }

        return max(0, (int) $this->max_registrants - (int) $this->registrants()->count());
    }

    public function hasSeatAvailable(): bool
    {
        $remaining = $this->seatsRemaining();

        return $remaining === null || $remaining > 0;
    }
}
