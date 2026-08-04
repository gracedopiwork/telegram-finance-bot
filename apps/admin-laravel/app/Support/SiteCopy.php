<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Baca teks marketing dari Site Settings (Admin), dengan fallback default.
 */
final class SiteCopy
{
    public static function get(string $key, ?string $default = null): string
    {
        if (! Schema::hasTable('site_settings')) {
            return (string) ($default ?? '');
        }

        $val = Setting::val($key, $default);

        return is_string($val) ? $val : (string) ($default ?? '');
    }

    /**
     * @return array<string, string>
     */
    public static function group(string $group): array
    {
        if (! Schema::hasTable('site_settings')) {
            return [];
        }

        return Setting::query()
            ->where('group', $group)
            ->orderBy('sort')
            ->pluck('value', 'key')
            ->map(fn ($v) => (string) ($v ?? ''))
            ->all();
    }

    /**
     * Multiline setting → list of non-empty lines.
     *
     * @return list<string>
     */
    public static function lines(string $key, array $default = []): array
    {
        $raw = self::get($key, implode("\n", $default));
        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out !== [] ? $out : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $raw = preg_replace('/[^\d]/', '', self::get($key, (string) $default)) ?? '';

        return $raw === '' ? $default : (int) $raw;
    }
}
