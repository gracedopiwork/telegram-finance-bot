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
    public function hasBotPortalAccess(string $email, int $telegramUserId = 0): bool
    {
        return $this->onboarding->hasPaidBotOrderForUser($email, $telegramUserId);
    }

    /**
     * Pembeli FTSA saja — portal terbatas (baseline FTSA, behavioral, premium).
     */
    public function isFtsaOnlyPortalUser(string $email, int $telegramUserId = 0): bool
    {
        if ($this->hasBotPortalAccess($email, $telegramUserId)) {
            return false;
        }

        return $this->onboarding->isFtsaOnlyBuyer($email);
    }

    public function isFtsaOnlyOrder(Order $order): bool
    {
        $ctx = $this->onboarding->orderDeliveryContext($order);

        return $ctx['is_ftsa_only'];
    }

    /**
     * ID portal untuk lisensi FTSA-only (offset tinggi, tidak bentrok Telegram).
     */
    public function syntheticUserIdBase(): int
    {
        return max(1_000_000_000_000, (int) config('portal.synthetic_user_id_base', 9_000_000_000_000));
    }

    public function syntheticPortalUserId(int $licenseId): int
    {
        return $this->syntheticUserIdBase() + abs($licenseId);
    }

    public function isSyntheticPortalUserId(int $telegramUserId): bool
    {
        return $telegramUserId >= $this->syntheticUserIdBase();
    }

    public function licenseIdFromSyntheticUserId(int $portalUserId): ?int
    {
        if (! $this->isSyntheticPortalUserId($portalUserId)) {
            return null;
        }

        $licenseId = $portalUserId - $this->syntheticUserIdBase();

        return $licenseId > 0 ? $licenseId : null;
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
    public function defaultPortalHomeRoute(string $email, int $telegramUserId = 0): string
    {
        if ($this->hasBotPortalAccess($email, $telegramUserId)) {
            return 'portal.dashboard';
        }

        return 'portal.emotional';
    }
}
