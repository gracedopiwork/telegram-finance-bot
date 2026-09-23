<?php

namespace Tests;

use App\Services\PortalPasswordService;
use App\Support\PortalSession;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Portal auth is hand-rolled (App\Support\PortalSession) — no guard, no User model,
     * so actingAs() does not work here. This mints the session keys the portal middleware
     * looks for, plus the portal password row that EnsurePortalPasswordSet demands
     * (without it every portal route redirects to /portal/akun).
     *
     * @return array{telegram_user_id: int, email: string}
     */
    protected function portalLogin(
        int $telegramUserId = 555_001,
        string $email = 'portal-test@example.com',
        string $displayName = 'Portal Test',
        string $userType = 'licensed',
    ): array {
        $email = strtolower($email);

        $passwords = app(PortalPasswordService::class);
        if ($passwords->isReady()) {
            $passwords->setPassword($email, 'rahasia-test-123');
        }

        $this->withSession([
            PortalSession::TELEGRAM_USER_ID => $telegramUserId,
            PortalSession::DISPLAY_NAME => $displayName,
            PortalSession::EMAIL => $email,
            PortalSession::USER_TYPE => $userType,
        ]);

        return ['telegram_user_id' => $telegramUserId, 'email' => $email];
    }
}
