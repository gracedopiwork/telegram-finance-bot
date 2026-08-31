<?php

namespace App\Services;

use App\Models\PortalGuidanceSnapshot;
use App\Support\PortalTimezone;
use Carbon\Carbon;

class PortalGuidanceSnapshotService
{
    /**
     * @return array{payload: array<string, mixed>, ai_source: string, generated_at: string}|null
     */
    public function get(int $telegramUserId, string $guidanceType, string $periodKey): ?array
    {
        $row = PortalGuidanceSnapshot::findForUser($telegramUserId, $guidanceType, $periodKey);
        if ($row === null) {
            return null;
        }

        return [
            'payload' => is_array($row->payload) ? $row->payload : [],
            'ai_source' => $row->ai_provider === 'claude' ? 'ai' : (string) $row->ai_provider,
            'generated_at' => $row->generated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(
        int $telegramUserId,
        string $guidanceType,
        string $periodKey,
        array $payload,
        string $aiProvider = 'claude',
    ): PortalGuidanceSnapshot {
        return PortalGuidanceSnapshot::query()->updateOrCreate(
            [
                'telegram_user_id' => $telegramUserId,
                'guidance_type' => $guidanceType,
                'period_key' => $periodKey,
            ],
            [
                'ai_provider' => $aiProvider,
                'payload' => $payload,
                'generated_at' => now(),
            ],
        );
    }

    public function weekRange(?Carbon $anchor = null): array
    {
        $anchor ??= now();

        return [
            'start' => $anchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            'end' => $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
            'key' => PortalGuidanceSnapshot::weekPeriodKey($anchor),
        ];
    }

    /**
     * Rentang kumulatif dalam bulan: minggu 1 = hari 1–7, minggu 2 = 1–14, … minggu 4 = 1–akhir bulan.
     *
     * @return array{start: Carbon, end: Carbon, key: string, week_in_month: int, label: string}
     */
    public function monthCumulativeWeekRange(?Carbon $anchor = null): array
    {
        $tz = PortalTimezone::defaultName();
        $anchor = ($anchor ?? now($tz))->copy()->timezone($tz);
        $monthStart = $anchor->copy()->startOfMonth()->startOfDay();
        $weekInMonth = PortalGuidanceSnapshot::monthCumulativeWeekNumber($anchor);

        if ($weekInMonth >= 4) {
            $end = $anchor->copy()->endOfMonth()->endOfDay();
        } else {
            $end = $monthStart->copy()->addDays($weekInMonth * 7 - 1)->endOfDay();
        }

        $label = sprintf(
            'Akumulasi minggu ke-%d (%s – %s)',
            $weekInMonth,
            $monthStart->translatedFormat('d M'),
            $end->translatedFormat('d M Y'),
        );

        return [
            'start' => $monthStart->copy()->utc(),
            'end' => $end->copy()->utc(),
            'key' => PortalGuidanceSnapshot::monthCumulativeWeekPeriodKey($anchor),
            'week_in_month' => $weekInMonth,
            'label' => $label,
        ];
    }

    public function monthRange(?Carbon $anchor = null): array
    {
        $tz = PortalTimezone::defaultName();
        $anchor = ($anchor ?? now($tz))->copy()->timezone($tz);

        return [
            'start' => $anchor->copy()->startOfMonth()->startOfDay()->utc(),
            'end' => $anchor->copy()->endOfMonth()->endOfDay()->utc(),
            'key' => PortalGuidanceSnapshot::monthPeriodKey($anchor),
        ];
    }
}
