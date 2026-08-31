<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConsultationSlot extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_HELD = 'held';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const HOLD_MINUTES = 45;

    /** Booking paling lambat H-1 agar planner sempat siapkan materi. */
    public const MIN_LEAD_HOURS = 24;

    /** Zona waktu operasional booking (WIB). */
    public const BOOKING_TIMEZONE = 'Asia/Jakarta';

    /** Klien wajib isi form screening sebelum sesi. */
    public const INTAKE_FORM_HOURS = 8;

    protected $fillable = [
        'advisor_id',
        'starts_at',
        'ends_at',
        'status',
        'held_until',
        'booking_code',
        'service_type',
        'guest_name',
        'guest_phone',
        'guest_age',
        'financial_stage',
        'stage_key',
        'amount_due',
        'order_id',
        'situation',
        'notes',
        'confirmed_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'held_until' => 'datetime',
        'confirmed_at' => 'datetime',
        'guest_age' => 'integer',
        'amount_due' => 'integer',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(CpAdvisor::class, 'advisor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeBookable(Builder $query): Builder
    {
        // Bandingkan wall-clock WIB: slot yang sudah lewat / < H-1 tidak ditampilkan.
        $cutoff = self::bookableCutoff()->format('Y-m-d H:i:s');

        return $query
            ->where('status', self::STATUS_OPEN)
            ->where('starts_at', '>', $cutoff);
    }

    public function isBeyondMinLead(?\DateTimeInterface $at = null): bool
    {
        $at = $at
            ? \Carbon\Carbon::parse($at, self::BOOKING_TIMEZONE)
            : \Carbon\Carbon::now(self::BOOKING_TIMEZONE);

        $starts = $this->starts_at->copy()->timezone(self::BOOKING_TIMEZONE);

        return $starts->greaterThan($at->copy()->addHours(self::MIN_LEAD_HOURS));
    }

    /**
     * Ambang waktu minimum untuk booking (sekarang WIB + MIN_LEAD_HOURS).
     */
    public static function bookableCutoff(?\DateTimeInterface $at = null): \Carbon\Carbon
    {
        $at = $at
            ? \Carbon\Carbon::parse($at, self::BOOKING_TIMEZONE)
            : \Carbon\Carbon::now(self::BOOKING_TIMEZONE);

        return $at->copy()->addHours(self::MIN_LEAD_HOURS);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now()->startOfDay());
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isHeld(): bool
    {
        return $this->status === self::STATUS_HELD;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isExpiredHold(): bool
    {
        return $this->isHeld()
            && $this->held_until !== null
            && $this->held_until->isPast();
    }

    public function labelTimeRange(): string
    {
        return $this->starts_at->format('H:i').'–'.$this->ends_at->format('H:i');
    }

    public function labelDate(): string
    {
        return $this->starts_at->translatedFormat('l, d M Y');
    }

    public static function generateBookingCode(): string
    {
        do {
            $code = 'YFD-'.strtoupper(Str::random(8));
        } while (self::query()->where('booking_code', $code)->exists());

        return $code;
    }
}
