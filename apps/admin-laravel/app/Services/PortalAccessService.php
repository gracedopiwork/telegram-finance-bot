<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;
use App\Support\PortalSession;
use Illuminate\Http\Request;

class PortalAccessService
{
    public function __construct(
        private readonly PortalOnboardingService $onboarding,
        private readonly LicenseEntitlementService $entitlements,
        private readonly LicenseProvisioningService $provisioning,
    ) {}

    /**
     * Akses dashboard bot (transaksi, KPI, input data) — berdasarkan lisensi portal aktif.
     */
    public function hasBotPortalAccess(string $email, int $telegramUserId = 0): bool
    {
        $license = $this->resolvePortalLicense($email, $telegramUserId);
        if ($license !== null) {
            return $this->entitlements->hasPaidBotOrderOnLicense($license);
        }

        if ($this->onboarding->isFtsaOnlyBuyer($email)) {
            return false;
        }

        return $this->onboarding->hasPaidBotOrderForUser($email, $telegramUserId);
    }

    /**
     * Pembeli FTSA saja — portal terbatas (diagnostik, FTSA, hasil behavioral).
     */
    public function isFtsaOnlyPortalUser(string $email, int $telegramUserId = 0): bool
    {
        if ($this->hasBotPortalAccess($email, $telegramUserId)) {
            return false;
        }

        $license = $this->resolvePortalLicense($email, $telegramUserId);
        if ($license !== null) {
            return $this->entitlements->hasPaidFtsaOrderOnLicense($license);
        }

        return $this->onboarding->isFtsaOnlyBuyer($email);
    }

    /**
     * Sinkronkan tipe sesi portal setelah hak akses berubah (tanpa wajib logout).
     */
    public function syncSessionUserType(Request $request, string $email, int $telegramUserId): void
    {
        if (! PortalSession::isAuthenticated($request)) {
            return;
        }

        $userType = $this->isFtsaOnlyPortalUser($email, $telegramUserId) ? 'ftsa_only' : 'licensed';
        if (PortalSession::userType($request) !== $userType) {
            $request->session()->put(PortalSession::USER_TYPE, $userType);
        }
    }

    public function resolvePortalLicense(string $email, int $telegramUserId = 0): ?License
    {
        $email = strtolower(trim($email));

        if ($telegramUserId > 0 && $this->isSyntheticPortalUserId($telegramUserId)) {
            $licenseId = $this->licenseIdFromSyntheticUserId($telegramUserId);
            if ($licenseId !== null) {
                $license = License::query()
                    ->whereKey($licenseId)
                    ->where('status', 'active')
                    ->first();
                if ($license !== null) {
                    return $license;
                }
            }
        }

        if ($email !== '') {
            $fromEmail = $this->provisioning->findExistingLicenseForEmail($email);
            if ($fromEmail !== null) {
                return $fromEmail;
            }
        }

        if ($telegramUserId > 0) {
            return License::query()
                ->where('assigned_user_id', $telegramUserId)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->first();
        }

        return null;
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
