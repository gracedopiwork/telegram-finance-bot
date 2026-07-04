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
     * Akses dashboard bot (transaksi, KPI, input data).
     */
    public function hasBotPortalAccess(string $email, int $telegramUserId = 0): bool
    {
        $request = request();
        if ($request !== null
            && PortalSession::isAuthenticated($request)
            && PortalSession::userType($request) === 'ftsa_only') {
            $sessionLicense = $this->resolveSessionLicense();
            if ($sessionLicense === null
                || ! $this->entitlements->hasPaidBotOrderOnLicense($sessionLicense)) {
                return false;
            }
        }

        if ($this->isFtsaOnlyPortalUser($email, $telegramUserId)) {
            return false;
        }

        $sessionLicense = $this->resolveSessionLicense();
        if ($sessionLicense !== null) {
            return $this->entitlements->hasPaidBotOrderOnLicense($sessionLicense);
        }

        $license = $this->resolvePortalLicense($email, $telegramUserId);
        if ($license !== null) {
            return $this->entitlements->hasPaidBotOrderOnLicense($license);
        }

        return $this->onboarding->hasPaidBotOrderForUser($email, $telegramUserId);
    }

    /**
     * Pembeli FTSA saja — portal terbatas (FTSA 1–32 + hasil behavioral).
     */
    public function isFtsaOnlyPortalUser(string $email, int $telegramUserId = 0): bool
    {
        $sessionLicense = $this->resolveSessionLicense();
        if ($sessionLicense !== null) {
            return $this->entitlements->hasPaidFtsaOrderOnLicense($sessionLicense)
                && ! $this->entitlements->hasPaidBotOrderOnLicense($sessionLicense);
        }

        $request = request();
        if ($request !== null
            && PortalSession::isAuthenticated($request)
            && PortalSession::userType($request) === 'ftsa_only') {
            return true;
        }

        $license = $this->resolvePortalLicense($email, $telegramUserId);
        if ($license !== null) {
            return $this->entitlements->hasPaidFtsaOrderOnLicense($license)
                && ! $this->entitlements->hasPaidBotOrderOnLicense($license);
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

        $sessionLicense = $this->resolveSessionLicense();
        if ($sessionLicense !== null && PortalSession::userType($request) === 'ftsa_only') {
            $stillFtsaOnly = $this->entitlements->hasPaidFtsaOrderOnLicense($sessionLicense)
                && ! $this->entitlements->hasPaidBotOrderOnLicense($sessionLicense);
            if ($stillFtsaOnly) {
                return;
            }
        }

        $license = $sessionLicense ?? $this->resolvePortalLicense($email, $telegramUserId);
        if ($license !== null && PortalSession::licenseId($request) === null) {
            $request->session()->put(PortalSession::LICENSE_ID, (int) $license->id);
        }

        $userType = $this->isFtsaOnlyPortalUser($email, $telegramUserId) ? 'ftsa_only' : 'licensed';
        if (PortalSession::userType($request) !== $userType) {
            $request->session()->put(PortalSession::USER_TYPE, $userType);
        }
    }

    public function resolvePortalLicense(string $email, int $telegramUserId = 0): ?License
    {
        $sessionLicense = $this->resolveSessionLicense();
        if ($sessionLicense !== null) {
            return $sessionLicense;
        }

        $email = strtolower(trim($email));

        if ($email !== '') {
            $ftsaOnlyLicense = $this->resolveFtsaOnlyPortalLicense($email);
            if ($ftsaOnlyLicense !== null) {
                return $ftsaOnlyLicense;
            }
        }

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
            $fromLatestOrder = $this->resolveLicenseFromLatestPaidOrder($email);
            if ($fromLatestOrder !== null) {
                return $fromLatestOrder;
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

    private function resolveSessionLicense(): ?License
    {
        $request = request();
        if ($request === null || ! PortalSession::isAuthenticated($request)) {
            return null;
        }

        $sessionLicenseId = PortalSession::licenseId($request);
        if ($sessionLicenseId === null) {
            return null;
        }

        return License::query()
            ->whereKey($sessionLicenseId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Lisensi dari pembelian FTSA pertama (bukan upgrade bot) tanpa order bot pada lisensi yang sama.
     */
    private function resolveFtsaOnlyPortalLicense(string $email): ?License
    {
        $ftsaCodes = $this->onboarding->ftsaUnlockProductCodes();
        if ($ftsaCodes === []) {
            return null;
        }

        $orders = Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->whereNotNull('license_id')
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $ftsaCodes))
            ->with('digitalProduct')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        foreach ($orders as $order) {
            if (! $this->isFtsaOnlyOrder($order)) {
                continue;
            }

            $license = License::query()
                ->whereKey($order->license_id)
                ->where('status', 'active')
                ->first();

            if ($license === null) {
                continue;
            }

            if (! $this->entitlements->hasPaidBotOrderOnLicense($license)) {
                return $license;
            }
        }

        return null;
    }

    private function resolveLicenseFromLatestPaidOrder(string $email): ?License
    {
        $order = Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->whereNotNull('license_id')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        if ($order === null) {
            return null;
        }

        return License::query()
            ->whereKey($order->license_id)
            ->where('status', 'active')
            ->first();
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
