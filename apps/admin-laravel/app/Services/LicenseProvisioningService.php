<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;
use Illuminate\Support\Str;

class LicenseProvisioningService
{
    public function __construct(
        private readonly LicenseEntitlementService $entitlements,
    ) {}

    /**
     * Satu lisensi per email — dipakai untuk FTSA setelah bot maupun bot setelah FTSA.
     */
    public function resolveLicenseForPaidOrder(Order $order): License
    {
        $order->loadMissing('digitalProduct');

        $existing = $this->findExistingLicenseForEmail((string) $order->email);
        if ($existing !== null) {
            $this->syncEntitlementsOntoLicense($order, $existing);

            return $existing->fresh() ?? $existing;
        }

        return License::create([
            'license_key' => $this->generateLicenseKey(),
            'plan' => $order->plan ?? ($order->digitalProduct?->code ?? 'manual'),
            'status' => 'active',
            'expires_at' => $this->entitlements->expiresAtForNewLicense($order),
            'max_accounts' => 1,
        ]);
    }

    public function findExistingLicenseForEmail(string $email): ?License
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $priorOrder = Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNotNull('license_id')
            ->orderByDesc('id')
            ->first();

        if ($priorOrder === null) {
            return null;
        }

        return License::query()
            ->whereKey($priorOrder->license_id)
            ->where('status', 'active')
            ->first();
    }

    public function hasPaidBotOrderOnLicense(License $license): bool
    {
        return $this->entitlements->hasPaidBotOrderOnLicense($license);
    }

    public function syncEntitlementsOntoLicense(Order $order, License $license): void
    {
        $code = (string) ($order->digitalProduct?->code ?? $order->plan ?? '');

        $updates = [];

        if ($this->entitlements->isBotAdminRenewalCode($code) || $this->entitlements->isBotProductCode($code)) {
            $updates['expires_at'] = $this->entitlements->expiresAtForNewLicense(
                $order,
                $license->expires_at instanceof \Carbon\Carbon ? $license->expires_at : null,
            );
            $updates['status'] = 'active';
        } elseif ($this->entitlements->isFtsaProductCode($code) && ! $this->hasPaidBotOrderOnLicense($license)) {
            $updates['expires_at'] = $this->entitlements->expiresAtForNewLicense($order);
        }

        $license->forceFill($updates)->save();

        $this->refreshLicensePlanFromOrders($license);
    }

    public function refreshLicensePlanFromOrders(License $license): void
    {
        $slug = $this->entitlements->resolveStoredPlanForLicense($license);

        if ($slug !== '' && $slug !== (string) $license->plan) {
            $license->forceFill(['plan' => $slug])->save();
        }
    }

    public function generateLicenseKey(): string
    {
        return 'TFB-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
    }
}
