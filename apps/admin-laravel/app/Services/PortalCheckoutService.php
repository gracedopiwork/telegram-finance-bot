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
        private readonly MidtransService $midtrans,
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
     * @return array{order: Order, snap_token: string}
     */
    public function createFtsaSnapCheckout(string $email, int $telegramUserId, string $fullName): array
    {
        if ($this->entitlements->hasActiveFtsaEntitlement($telegramUserId)
            || $this->entitlements->hasActiveFtsaEntitlementForEmail($email)) {
            throw ValidationException::withMessages([
                'product' => 'FTSA Premium sudah aktif di akun Anda.',
            ]);
        }

        $product = $this->product();
        abort_if($product->billing_mode !== 'midtrans', 422, 'Produk ini belum dapat dibeli.');

        $finalAmount = $product->effective_price;
        abort_if($finalAmount <= 0, 422, 'Harga produk belum diatur.');

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
            'payment_gateway' => 'midtrans',
            'license_id' => $licenseId,
        ]);

        try {
            $payment = $this->midtrans->createSnapTransaction([
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
            ]);
            $order->payment_token = $payment['token'] ?? null;
            $order->payment_url = $payment['redirect_url'] ?? null;
            $order->save();
        } catch (\Throwable $e) {
            Log::warning('Portal FTSA Snap gagal', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat pembayaran. Silakan coba lagi atau hubungi tim YFD.',
            ]);
        }

        $token = (string) ($order->payment_token ?? '');
        if ($token === '') {
            throw ValidationException::withMessages([
                'payment' => 'Token pembayaran tidak tersedia. Cek konfigurasi Midtrans.',
            ]);
        }

        return ['order' => $order, 'snap_token' => $token];
    }

    /**
     * @return array{order: Order, snap_token: string}
     */
    public function createBotSnapCheckout(string $email, int $telegramUserId, string $fullName): array
    {
        if (! $this->canUpgradeBotInPortal($email, $telegramUserId)) {
            throw ValidationException::withMessages([
                'product' => 'YFD First Aid sudah aktif atau upgrade tidak tersedia untuk akun ini.',
            ]);
        }

        if (! $this->midtrans->isSnapReady()) {
            throw ValidationException::withMessages([
                'payment' => 'Midtrans belum dikonfigurasi di server. Hubungi tim YFD.',
            ]);
        }

        $product = $this->botProduct();
        abort_if($product->billing_mode !== 'midtrans', 422, 'Produk ini belum dapat dibeli.');

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
            'payment_gateway' => 'midtrans',
            'license_id' => $licenseId,
        ]);

        try {
            $payment = $this->midtrans->createSnapTransaction([
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
            ]);
            $order->payment_token = $payment['token'] ?? null;
            $order->payment_url = $payment['redirect_url'] ?? null;
            $order->save();
        } catch (\Throwable $e) {
            Log::warning('Portal bot Snap gagal', [
                'order_code' => $order->order_code,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat pembayaran. Silakan coba lagi atau hubungi tim YFD.',
            ]);
        }

        $token = (string) ($order->payment_token ?? '');
        if ($token === '') {
            throw ValidationException::withMessages([
                'payment' => 'Token pembayaran tidak tersedia. Cek konfigurasi Midtrans.',
            ]);
        }

        return ['order' => $order, 'snap_token' => $token];
    }

    public function canUpgradeBotInPortal(string $email, int $telegramUserId = 0): bool
    {
        return $this->botUpgradeEligibility($email, $telegramUserId)['eligible'];
    }

    /**
     * @return array{
     *     can_pay: bool,
     *     product_missing: bool,
     *     midtrans_ready: bool,
     *     eligible: bool
     * }
     */
    public function botUpgradeEligibility(string $email, int $telegramUserId = 0): array
    {
        $email = strtolower(trim($email));
        $midtransReady = $this->midtrans->isSnapReady();

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
                'midtrans_ready' => $midtransReady,
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
            'can_pay' => ! $productMissing && $midtransReady && $eligible,
            'product_missing' => $productMissing,
            'midtrans_ready' => $midtransReady,
            'eligible' => $eligible,
        ];
    }

    public function orderBelongsToSession(Order $order, string $email): bool
    {
        return strtolower(trim((string) $order->email)) === strtolower(trim($email));
    }

}
