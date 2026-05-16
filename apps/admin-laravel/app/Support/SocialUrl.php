<?php

namespace App\Support;

/**
 * Normalisasi link profil sosial dari site_settings (username atau URL lengkap).
 */
final class SocialUrl
{
    public static function instagram(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '#';
        }
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }

        return 'https://www.instagram.com/'.ltrim($raw, '@/');
    }

    public static function tiktok(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '#';
        }
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }

        return 'https://www.tiktok.com/@'.ltrim($raw, '@/');
    }

    public static function threads(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '#';
        }
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            return $raw;
        }

        return 'https://www.threads.net/@'.ltrim($raw, '@/');
    }
}
