<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class PortalTimezone
{
    public static function name(): string
    {
        return (string) config('portal.display_timezone', 'Asia/Jakarta');
    }

    public static function nowUtc(): Carbon
    {
        return Carbon::now('UTC');
    }

    public static function formatRecordedAt(CarbonInterface $value): string
    {
        return $value->copy()->utc()->timezone(self::name())->format('d-m-Y H:i') . ' WIB';
    }

    public static function parseRecordedAt(?string $value): Carbon
    {
        if ($value === null || trim($value) === '') {
            return self::nowUtc();
        }

        $trimmed = trim($value);

        // ISO / offset-aware strings: respect embedded timezone, store as UTC.
        if (preg_match('/[zZ]|[+-]\d{2}:?\d{2}$/', $trimmed)) {
            return Carbon::parse($trimmed)->utc();
        }

        // Naive timestamps from bot/CSV are WIB local time.
        return Carbon::parse($trimmed, self::name())->utc();
    }
}
