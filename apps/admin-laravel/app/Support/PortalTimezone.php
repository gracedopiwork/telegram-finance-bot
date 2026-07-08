<?php

namespace App\Support;

use App\Services\PortalUserTimezoneService;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class PortalTimezone
{
    public static function defaultName(): string
    {
        return (string) config('portal.display_timezone', 'Asia/Jakarta');
    }

    public static function forUser(?int $telegramUserId): string
    {
        if ($telegramUserId === null || $telegramUserId <= 0) {
            return self::defaultName();
        }

        return app(PortalUserTimezoneService::class)->resolve($telegramUserId);
    }

    public static function labelFor(?int $telegramUserId = null, ?string $iana = null): string
    {
        $tz = $iana ?? self::forUser($telegramUserId);

        return app(PortalUserTimezoneService::class)->labelFor($tz);
    }

    public static function nowUtc(): Carbon
    {
        return Carbon::now('UTC');
    }

    public static function formatRecordedAt(CarbonInterface $value, ?int $telegramUserId = null, ?string $iana = null): string
    {
        $tz = $iana ?? self::forUser($telegramUserId);
        $label = app(PortalUserTimezoneService::class)->labelFor($tz);

        return $value->copy()->utc()->timezone($tz)->format('d-m-Y H:i') . ' ' . $label;
    }

    public static function parseRecordedAt(?string $value, ?int $telegramUserId = null): Carbon
    {
        if ($value === null || trim($value) === '') {
            return self::nowUtc();
        }

        $trimmed = trim($value);
        $userTz = self::forUser($telegramUserId);

        // ISO / offset-aware strings: respect embedded timezone, store as UTC.
        if (preg_match('/[zZ]|[+-]\d{2}:?\d{2}$/', $trimmed)) {
            return Carbon::parse($trimmed)->utc();
        }

        // Naive timestamps from bot/CSV are local time in the user's zone.
        return Carbon::parse($trimmed, $userTz)->utc();
    }
}
