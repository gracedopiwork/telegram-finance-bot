<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;

class PortalAccessService
{
    public function __construct(
        private readonly PortalOnboardingService $onboarding,
    ) {}

    /**
     * Akses dashboard bot (transaksi, KPI, input data).
     */
    public function hasBotPortalAccess(string $email): bool
    {
        return $this->onboarding->hasPaidBotOrder($email);
    }

    /**
     * Pembeli FTSA saja — portal terbatas (baseline FTSA, behavioral, premium).
     */
    public function isFtsaOnlyPortalUser(string $email): bool
    {
        return $this->onboarding->isFtsaOnlyBuyer($email);
    }

    public function isFtsaOnlyOrder(Order $order): bool
    {
        $ctx = $this->onboarding->orderDeliveryContext($order);

        return $ctx['is_ftsa_only'];
    }

    /**
     * ID portal untuk lisensi FTSA-only (negatif agar tidak bentrok dengan Telegram).
     */
    public function syntheticPortalUserId(int $licenseId): int
    {
        return -1 * abs($licenseId);
    }

    public function isSyntheticPortalUserId(int $telegramUserId): bool
    {
        return $telegramUserId < 0;
    }

    /**
     * Aktivasi portal web untuk pembeli FTSA-only (tanpa /activate di bot).
     */
    public function ensureLicensePortalActivation(License $license): int
    {
        if ($license->assigned_user_id) {
            return (int) $license->assigned_user_id;
        }

        $portalUserId = $this->syntheticPortalUserId((int) $license->id);
        $license->forceFill([
            'assigned_user_id' => $portalUserId,
            'activated_at' => now(),
            'status' => 'active',
        ])->save();

        return $portalUserId;
    }

    /**
     * URL landing portal setelah login.
     */
    public function defaultPortalHomeRoute(string $email): string
    {
        if ($this->hasBotPortalAccess($email)) {
            return 'portal.dashboard';
        }

        return 'portal.emotional';
    }
}
