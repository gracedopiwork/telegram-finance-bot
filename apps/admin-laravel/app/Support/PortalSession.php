<?php

namespace App\Support;

use Illuminate\Http\Request;

class PortalSession
{
    public const TELEGRAM_USER_ID = 'portal.telegram_user_id';

    public const DISPLAY_NAME = 'portal.display_name';

    public const EMAIL = 'portal.email';

    public static function telegramUserId(Request $request): ?int
    {
        $id = $request->session()->get(self::TELEGRAM_USER_ID);

        return $id ? (int) $id : null;
    }

    public static function isAuthenticated(Request $request): bool
    {
        return self::telegramUserId($request) !== null;
    }

    public static function login(Request $request, int $telegramUserId, string $displayName, string $email): void
    {
        $request->session()->put(self::TELEGRAM_USER_ID, $telegramUserId);
        $request->session()->put(self::DISPLAY_NAME, $displayName);
        $request->session()->put(self::EMAIL, $email);
    }

    public static function logout(Request $request): void
    {
        $request->session()->forget([self::TELEGRAM_USER_ID, self::DISPLAY_NAME, self::EMAIL]);
    }
}
