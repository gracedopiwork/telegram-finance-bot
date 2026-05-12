<?php

namespace App\Support;

use App\Models\Setting;

final class TelegramBotUrl
{
    /**
     * URL t.me untuk email & halaman web. Prioritas: site_settings `telegram.bot_url` lalu env.
     */
    public static function resolve(): ?string
    {
        try {
            $db = trim((string) (Setting::val('telegram.bot_url') ?? ''));
            if ($db !== '') {
                if (filter_var($db, FILTER_VALIDATE_URL)) {
                    return $db;
                }
                $u = ltrim($db, '@');

                return $u !== '' ? 'https://t.me/'.rawurlencode($u) : null;
            }
        } catch (\Throwable) {
            // DB belum siap / migrasi
        }

        return self::fromEnv();
    }

    public static function fromEnv(): ?string
    {
        $url = trim((string) config('services.telegram.bot_url', ''));
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        $user = trim((string) config('services.telegram.bot_username', ''));
        $user = ltrim($user, '@');

        return $user !== '' ? 'https://t.me/'.rawurlencode($user) : null;
    }
}
