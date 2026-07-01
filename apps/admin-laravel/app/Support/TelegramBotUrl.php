<?php

namespace App\Support;

use App\Models\Setting;

final class TelegramBotUrl
{
    /**
     * URL https://t.me/... untuk email & halaman web.
     */
    public static function resolve(): ?string
    {
        return self::webUrl();
    }

    /**
     * Deep link agar HP langsung buka aplikasi Telegram (bukan halaman "download").
     */
    public static function appDeepLink(): ?string
    {
        $username = self::username();

        return $username !== null ? 'tg://resolve?domain='.$username : null;
    }

    public static function webUrl(): ?string
    {
        $username = self::username();

        return $username !== null ? 'https://t.me/'.$username : null;
    }

    public static function username(): ?string
    {
        try {
            $db = trim((string) (Setting::val('telegram.bot_url') ?? ''));
            if ($db !== '') {
                $parsed = self::parseUsername($db);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        } catch (\Throwable) {
            // DB belum siap / migrasi
        }

        $url = trim((string) config('services.telegram.bot_url', ''));
        if ($url !== '') {
            $parsed = self::parseUsername($url);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        $user = trim((string) config('services.telegram.bot_username', ''));

        return self::parseUsername($user);
    }

    private static function parseUsername(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        try {
            if (preg_match('~(?:https?://)?(?:www\.)?t\.me/([A-Za-z0-9_]{4,32})(?:[/?#]|$)~i', $raw, $matches)) {
                return $matches[1];
            }

            if (preg_match('~tg://resolve\?domain=([A-Za-z0-9_]{4,32})~i', $raw, $matches)) {
                return $matches[1];
            }

            if (preg_match('~(?:https?://)?(?:www\.)?telegram\.(?:me|dog)/([A-Za-z0-9_]{4,32})(?:[/?#]|$)~i', $raw, $matches)) {
                return $matches[1];
            }

            $candidate = ltrim($raw, '@');
            if (preg_match('~^[A-Za-z0-9_]{4,32}$~', $candidate)) {
                return $candidate;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
