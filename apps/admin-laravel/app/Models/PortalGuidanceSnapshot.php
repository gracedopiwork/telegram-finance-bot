<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PortalGuidanceSnapshot extends Model
{
    public const TYPE_CLINICAL_SUMMARY_WEEKLY = 'clinical_summary_weekly';

    public const TYPE_DOCTORS_NOTE_MONTHLY = 'doctors_note_monthly';

    public const TYPE_BEHAVIORAL_MONTHLY = 'behavioral_monthly';

    protected $fillable = [
        'telegram_user_id',
        'guidance_type',
        'period_key',
        'ai_provider',
        'payload',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public static function weekPeriodKey(?Carbon $date = null): string
    {
        $date ??= now();

        return sprintf('%d-W%02d', $date->isoWeekYear(), $date->isoWeek());
    }

    /**
     * Minggu ke-N dalam bulan (1–4), dipakai untuk clinical summary kumulatif.
     */
    public static function monthCumulativeWeekPeriodKey(?Carbon $date = null): string
    {
        $date ??= now();
        $weekInMonth = self::monthCumulativeWeekNumber($date);

        return sprintf('%s-W%d', $date->format('Y-m'), $weekInMonth);
    }

    public static function monthCumulativeWeekNumber(?Carbon $date = null): int
    {
        $date ??= now();

        return min(max((int) ceil($date->day / 7), 1), 4);
    }

    public static function monthPeriodKey(?Carbon $date = null): string
    {
        $date ??= now();

        return $date->format('Y-m');
    }

    public static function findForUser(int $telegramUserId, string $guidanceType, string $periodKey): ?self
    {
        return self::query()
            ->where('telegram_user_id', $telegramUserId)
            ->where('guidance_type', $guidanceType)
            ->where('period_key', $periodKey)
            ->first();
    }
}
