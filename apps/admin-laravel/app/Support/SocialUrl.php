<?php

namespace App\Support;

/**
 * Normalisasi link profil sosial dari site_settings: URL lengkap ditempel atau username saja.
 */
final class SocialUrl
{
    public static function instagram(string $raw): string
    {
        $raw = self::normalizeRaw($raw);
        if ($raw === '') {
            return '#';
        }
        if (self::hasHttpScheme($raw)) {
            return $raw;
        }
        if (str_starts_with($raw, '//')) {
            return 'https:'.$raw;
        }
        if (preg_match('#^(?:www\.)?instagram\.com(/|[\?\#]|$)#i', $raw)) {
            return 'https://'.ltrim($raw, '/');
        }

        return 'https://www.instagram.com/'.ltrim($raw, '@/');
    }

    public static function tiktok(string $raw): string
    {
        $raw = self::normalizeRaw($raw);
        if ($raw === '') {
            return '#';
        }
        if (self::hasHttpScheme($raw)) {
            return $raw;
        }
        if (str_starts_with($raw, '//')) {
            return 'https:'.$raw;
        }
        if (preg_match('#^(?:(?:vm|vt|www)\.)?tiktok\.com(/|[\?\#]|$)#i', $raw)) {
            return 'https://'.ltrim($raw, '/');
        }

        return 'https://www.tiktok.com/@'.ltrim($raw, '@/');
    }

    public static function threads(string $raw): string
    {
        $raw = self::normalizeRaw($raw);
        if ($raw === '') {
            return '#';
        }
        if (self::hasHttpScheme($raw)) {
            return $raw;
        }
        if (str_starts_with($raw, '//')) {
            return 'https:'.$raw;
        }
        if (preg_match('#^(?:www\.)?threads\.net(/|[\?\#]|$)#i', $raw)) {
            return 'https://'.ltrim($raw, '/');
        }

        return 'https://www.threads.net/@'.ltrim($raw, '@/');
    }

    private static function normalizeRaw(string $raw): string
    {
        $raw = trim($raw);

        return preg_replace('#^@+(?=https?://)#i', '', $raw) ?? $raw;
    }

    private static function hasHttpScheme(string $raw): bool
    {
        return (bool) preg_match('#^https?://#i', $raw);
    }
}
