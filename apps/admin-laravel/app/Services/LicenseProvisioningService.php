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
        $codes = $this->entitlements->botProductCodes();
        if ($codes === []) {
            return false;
        }

        return Order::query()
            ->where('status', 'paid')
            ->where('license_id', $license->id)
            ->whereHas('digitalProduct', fn ($q) => $q->whereIn('code', $codes))
            ->exists();
    }

    public function syncEntitlementsOntoLicense(Order $order, License $license): void
    {
        $code = (string) ($order->digitalProduct?->code ?? $order->plan ?? '');

        if ($this->entitlements->isBotProductCode($code)) {
            $license->forceFill(['expires_at' => null])->save();

            return;
        }

        if ($this->entitlements->isFtsaProductCode($code) && ! $this->hasPaidBotOrderOnLicense($license)) {
            $license->forceFill([
                'expires_at' => $this->entitlements->expiresAtForNewLicense($order),
            ])->save();
        }
    }

    public function generateLicenseKey(): string
    {
        return 'TFB-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
    }
}
