<?php

namespace App\Support;

use Illuminate\Http\Request;

class PortalSession
{
    public const TELEGRAM_USER_ID = 'portal.telegram_user_id';

    public const DISPLAY_NAME = 'portal.display_name';

    public const EMAIL = 'portal.email';

    public const USER_TYPE = 'portal.user_type';

    public const LICENSE_ID = 'portal.license_id';

    public static function telegramUserId(Request $request): ?int
    {
        $id = $request->session()->get(self::TELEGRAM_USER_ID);

        return $id ? (int) $id : null;
    }

    public static function isAuthenticated(Request $request): bool
    {
        return self::telegramUserId($request) !== null;
    }

    public static function email(Request $request): ?string
    {
        $email = $request->session()->get(self::EMAIL);

        return is_string($email) && $email !== '' ? strtolower($email) : null;
    }

    public static function userType(Request $request): string
    {
        $type = $request->session()->get(self::USER_TYPE);

        return is_string($type) && $type !== '' ? $type : 'free';
    }

    public static function login(
        Request $request,
        int $telegramUserId,
        string $displayName,
        string $email,
        string $userType = 'licensed',
        ?int $licenseId = null,
    ): void {
        $request->session()->put(self::TELEGRAM_USER_ID, $telegramUserId);
        $request->session()->put(self::DISPLAY_NAME, $displayName);
        $request->session()->put(self::EMAIL, strtolower($email));
        $request->session()->put(self::USER_TYPE, $userType);
        if ($licenseId !== null && $licenseId > 0) {
            $request->session()->put(self::LICENSE_ID, $licenseId);
        }
    }

    public static function licenseId(Request $request): ?int
    {
        $id = $request->session()->get(self::LICENSE_ID);

        return $id ? (int) $id : null;
    }

    public static function logout(Request $request): void
    {
        $request->session()->forget([self::TELEGRAM_USER_ID, self::DISPLAY_NAME, self::EMAIL, self::USER_TYPE, self::LICENSE_ID]);
    }
}
