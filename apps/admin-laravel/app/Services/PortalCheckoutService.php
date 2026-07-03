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

    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly LicenseEntitlementService $entitlements,
        private readonly LicenseProvisioningService $provisioning,
    ) {}

    public function product(): CpDigitalProduct
    {
        return CpDigitalProduct::active()->where('code', self::FTSA_PRODUCT_CODE)->firstOrFail();
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
        $license = $this->provisioning->findExistingLicenseForEmail($email);

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
            'license_id' => $license?->id,
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

    public function orderBelongsToSession(Order $order, string $email): bool
    {
        return strtolower(trim((string) $order->email)) === strtolower(trim($email));
    }

}
