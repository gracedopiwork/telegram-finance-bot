<?php

namespace App\Services;

use App\Models\Order;
use App\Support\PortalSession;
use Illuminate\Http\Request;

class PortalSessionSyncService
{
    public function __construct(
        private readonly PortalAccessService $portalAccess,
        private readonly BaselineClaimService $baselineClaim,
    ) {}

    /**
     * Setelah /activate di bot, sesi portal bisa masih memakai ID sintetis FTSA-only.
     * Sinkronkan ke Telegram user ID asli dari lisensi.
     */
    public function sync(Request $request): int
    {
        $sessionId = (int) PortalSession::telegramUserId($request);
        $email = strtolower(trim((string) (PortalSession::email($request) ?? '')));

        if ($sessionId <= 0 || $email === '') {
            return $sessionId;
        }

        $licenseUserId = $this->licenseTelegramUserIdForEmail($email);
        if ($licenseUserId !== null
            && $licenseUserId !== $sessionId
            && ! $this->portalAccess->isSyntheticPortalUserId($licenseUserId)) {
            $request->session()->put(PortalSession::TELEGRAM_USER_ID, $licenseUserId);
            $this->baselineClaim->claimForUser($email, $licenseUserId);

            return $licenseUserId;
        }

        $this->baselineClaim->claimForUser($email, $sessionId);

        return $sessionId;
    }

    private function licenseTelegramUserIdForEmail(string $email): ?int
    {
        $userId = Order::query()
            ->join('licenses', 'licenses.id', '=', 'orders.license_id')
            ->where('orders.status', 'paid')
            ->whereRaw('LOWER(orders.email) = ?', [$email])
            ->whereNotNull('licenses.assigned_user_id')
            ->orderByDesc('orders.id')
            ->value('licenses.assigned_user_id');

        if ($userId === null) {
            return null;
        }

        $userId = (int) $userId;

        return $userId > 0 ? $userId : null;
    }
}
