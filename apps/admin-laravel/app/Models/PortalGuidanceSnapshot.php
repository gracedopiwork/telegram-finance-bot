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
