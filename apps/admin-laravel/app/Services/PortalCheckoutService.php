<?php

namespace App\Services;

use App\Models\CpDigitalProduct;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PortalCheckoutService
{
    public const FTSA_PRODUCT_CODE = 'yfd-ftsa-premium';

    public const BOT_PRODUCT_CODE = 'yfd-bot-telegram';

    public function __construct(
        private readonly PivotService $pivot,
        private readonly LicenseEntitlementService $entitlements,
        private readonly LicenseProvisioningService $provisioning,
        private readonly PortalOnboardingService $onboarding,
        private readonly PortalAccessService $access,
    ) {}

    public function product(): CpDigitalProduct
    {
        return CpDigitalProduct::active()->where('code', self::FTSA_PRODUCT_CODE)->firstOrFail();
    }

    public function botProduct(): CpDigitalProduct
    {
        foreach ($this->entitlements->botProductCodes() as $code) {
            $product = CpDigitalProduct::active()->where('code', $code)->first();
            if ($product !== null) {
                return $product;
            }
        }

        return CpDigitalProduct::active()->where('code', self::BOT_PRODUCT_CODE)->firstOrFail();
    }

    public function suggestPhone(string $email): ?string
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $phone = Order::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderByDesc('id')
            ->value('phone');

        return is_string($phone) && $phone !== '' ? $phone : null;
    }

    /**
     * @return array{order: Order, payment_url: string}
     */
    public function createFtsaCheckout(string $email, int $telegramUserId, string $fullName): array
    {
        if ($this->entitlements->hasActiveFtsaEntitlement($telegramUserId)
            || $this->entitlements->hasActiveFtsaEntitlementForEmail($email)) {
            throw ValidationException::withMessages([
                'product' => 'FTSA Premium sudah aktif di akun Anda.',
            ]);
        }

        $product = $this->product();
        abort_if(! $this->isGatewayProduct($product), 422, 'Produk ini belum dapat dibeli.');

        $finalAmount = $product->effective_price;
        abort_if($finalAmount <= 0, 422, 'Harga produk belum diatur.');

        if (! $this->pivot->isReady()) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran belum dikonfigurasi di server. Hubungi tim YFD.',
            ]);
        }

        $phone = $this->suggestPhone($email);
        $existingLicense = $this->provisioning->findExistingLicenseForEmail($email);
        $licenseId = ($existingLicense !== null && $this->entitlements->hasPaidBotOrderOnLicense($existingLicense))
            ? $existingLicense->id
            : null;

        $order = Order::create([
            'order_code' => 'YFD-'.Str::upper(Str::random(10)),
            'full_name' => $fullName,
            'email' => strtolower(trim($email)),
            'phone' => $phone,
            'plan' => $product->code,
            'digital_product_id' => $product->id,
            'product_name' => $product->name,
            'amount' => $finalAmount,
            'original_price' => (int) $product->price,
            'discount_amount' => max(0, (int) $product->price - $finalAmount),
            'currency' => $product->currency ?? 'IDR',
            'status' => 'pending',
            'payment_gateway' => 'pivot',
            'license_id' => $licenseId,
        ]);

        try {
            $payment = $this->pivot->createRedirectPayment([
                'order_id' => $order->order_code,
                'gross_amount' => $order->amount,
                'full_name' => $order->full_name,
                'email' => $order->email,
                'phone' => $order->phone ?? '',
                'item_details' => [[
                    'id' => $product->code,
                    'price' => $finalAmount,
                    'quantity' => 1,
                    'name' => Str::limit($product->name, 50),
                ]],
                'success_return_url' => $this->portalReturnUrl($order->order_code, 'success'),
                'failure_return_url' => $this->portalReturnUrl($order->order_code, 'failure'),
                'expiration_return_url' => $this->portalReturnUrl($order->order_code, 'expired'),
            ]);
            $order->payment_token = $payment['id'] ?? null;
            $order->payment_url = $payment['payment_url'] ?? null;
            $order->save();
        } catch (\Throwable $e) {
            Log::warning('Portal FTSA Pivot gagal', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat pembayaran. Silakan coba lagi atau hubungi tim YFD.',
            ]);
        }

        $url = (string) ($order->payment_url ?? '');
        if ($url === '') {
            throw ValidationException::withMessages([
                'payment' => 'Link pembayaran tidak tersedia. Cek konfigurasi Pivot.',
            ]);
        }

        return ['order' => $order, 'payment_url' => $url];
    }

    /**
     * @return array{order: Order, payment_url: string}
     */
    public function createBotCheckout(string $email, int $telegramUserId, string $fullName): array
    {
        if (! $this->canUpgradeBotInPortal($email, $telegramUserId)) {
            throw ValidationException::withMessages([
                'product' => 'YFD First Aid sudah aktif atau upgrade tidak tersedia untuk akun ini.',
            ]);
        }

        if (! $this->pivot->isReady()) {
            throw ValidationException::withMessages([
                'payment' => 'Pivot belum dikonfigurasi di server. Hubungi tim YFD.',
            ]);
        }

        $product = $this->botProduct();
        abort_if(! $this->isGatewayProduct($product), 422, 'Produk ini belum dapat dibeli.');

        $finalAmount = $product->effective_price;
        abort_if($finalAmount <= 0, 422, 'Harga produk belum diatur.');

        $phone = $this->suggestPhone($email);
        $license = $this->access->resolvePortalLicense($email, $telegramUserId)
            ?? $this->provisioning->findExistingLicenseForEmail($email);
        $licenseId = $license?->id;

        $order = Order::create([
            'order_code' => 'YFD-'.Str::upper(Str::random(10)),
            'full_name' => $fullName,
            'email' => strtolower(trim($email)),
            'phone' => $phone,
            'plan' => $product->code,
            'digital_product_id' => $product->id,
            'product_name' => $product->name,
            'amount' => $finalAmount,
            'original_price' => (int) $product->price,
            'discount_amount' => max(0, (int) $product->price - $finalAmount),
            'currency' => $product->currency ?? 'IDR',
            'status' => 'pending',
            'payment_gateway' => 'pivot',
            'license_id' => $licenseId,
        ]);

        try {
            $payment = $this->pivot->createRedirectPayment([
                'order_id' => $order->order_code,
                'gross_amount' => $order->amount,
                'full_name' => $order->full_name,
                'email' => $order->email,
                'phone' => $order->phone ?? '',
                'item_details' => [[
                    'id' => $product->code,
                    'price' => $finalAmount,
                    'quantity' => 1,
                    'name' => Str::limit($product->name, 50),
                ]],
                'success_return_url' => $this->portalReturnUrl($order->order_code, 'success'),
                'failure_return_url' => $this->portalReturnUrl($order->order_code, 'failure'),
                'expiration_return_url' => $this->portalReturnUrl($order->order_code, 'expired'),
            ]);
            $order->payment_token = $payment['id'] ?? null;
            $order->payment_url = $payment['payment_url'] ?? null;
            $order->save();
        } catch (\Throwable $e) {
            Log::warning('Portal bot Pivot gagal', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat pembayaran. Silakan coba lagi atau hubungi tim YFD.',
            ]);
        }

        $url = (string) ($order->payment_url ?? '');
        if ($url === '') {
            throw ValidationException::withMessages([
                'payment' => 'Link pembayaran tidak tersedia. Cek konfigurasi Pivot.',
            ]);
        }

        return ['order' => $order, 'payment_url' => $url];
    }

    /** @deprecated Use createFtsaCheckout */
    public function createFtsaSnapCheckout(string $email, int $telegramUserId, string $fullName): array
    {
        $result = $this->createFtsaCheckout($email, $telegramUserId, $fullName);

        return ['order' => $result['order'], 'snap_token' => '', 'payment_url' => $result['payment_url']];
    }

    /** @deprecated Use createBotCheckout */
    public function createBotSnapCheckout(string $email, int $telegramUserId, string $fullName): array
    {
        $result = $this->createBotCheckout($email, $telegramUserId, $fullName);

        return ['order' => $result['order'], 'snap_token' => '', 'payment_url' => $result['payment_url']];
    }

    public function canUpgradeBotInPortal(string $email, int $telegramUserId = 0): bool
    {
        return $this->botUpgradeEligibility($email, $telegramUserId)['eligible'];
    }

    /**
     * @return array{
     *     can_pay: bool,
     *     product_missing: bool,
     *     pivot_ready: bool,
     *     midtrans_ready: bool,
     *     eligible: bool
     * }
     */
    public function botUpgradeEligibility(string $email, int $telegramUserId = 0): array
    {
        $email = strtolower(trim($email));
        $pivotReady = $this->pivot->isReady();

        $productMissing = false;
        try {
            $this->botProduct();
        } catch (\Throwable) {
            $productMissing = true;
        }

        if ($email === '') {
            return [
                'can_pay' => false,
                'product_missing' => $productMissing,
                'pivot_ready' => $pivotReady,
                'midtrans_ready' => $pivotReady,
                'eligible' => false,
            ];
        }

        $license = $this->access->resolvePortalLicense($email, $telegramUserId);
        if ($license !== null) {
            $eligible = $this->entitlements->hasPaidFtsaOrderOnLicense($license)
                && ! $this->entitlements->hasPaidBotOrderOnLicense($license);
        } elseif ($this->entitlements->hasPaidBotOrderForEmail($email)) {
            $eligible = false;
        } else {
            $eligible = $this->onboarding->isFtsaOnlyBuyer($email);
        }

        return [
            'can_pay' => ! $productMissing && $pivotReady && $eligible,
            'product_missing' => $productMissing,
            'pivot_ready' => $pivotReady,
            'midtrans_ready' => $pivotReady,
            'eligible' => $eligible,
        ];
    }

    public function orderBelongsToSession(Order $order, string $email): bool
    {
        return strtolower(trim((string) $order->email)) === strtolower(trim($email));
    }

    private function isGatewayProduct(CpDigitalProduct $product): bool
    {
        return in_array((string) $product->billing_mode, ['pivot', 'midtrans'], true);
    }

    private function portalReturnUrl(string $orderCode, string $result): string
    {
        return route('checkout.finish', [
            'order_id' => $orderCode,
            'result' => $result,
        ]);
    }
}
