<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;
use Illuminate\Support\Facades\URL;

class PortalAutoLoginService
{
    /** Masa berlaku link dari bot (menit). */
    private const LINK_TTL_MINUTES = 30;

    /**
     * @return array{telegram_user_id: int, display_name: string, email: string}|null
     */
    public function resolvePortalUser(int $telegramUserId): ?array
    {
        $license = License::query()
            ->where('assigned_user_id', $telegramUserId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (! $license) {
            return null;
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return null;
        }

        $order = Order::query()
            ->where('license_id', $license->id)
            ->where('status', 'paid')
            ->orderByDesc('id')
            ->first();

        if (! $order) {
            return null;
        }

        return [
            'telegram_user_id' => $telegramUserId,
            'display_name' => $license->assigned_username ?: $order->full_name,
            'email' => strtolower($order->email),
        ];
    }

    public function createSignedLoginUrl(int $telegramUserId): ?string
    {
        if ($this->resolvePortalUser($telegramUserId) === null) {
            return null;
        }

        return URL::temporarySignedRoute(
            'portal.auto-login',
            now()->addMinutes(self::LINK_TTL_MINUTES),
            ['uid' => $telegramUserId],
        );
    }
}
