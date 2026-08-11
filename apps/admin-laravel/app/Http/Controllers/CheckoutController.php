<?php

namespace App\Http\Controllers;

use App\Models\CpDigitalProduct;
use App\Models\Order;
use App\Services\MidtransPaymentSyncService;
use App\Services\MidtransService;
use App\Services\OrderDeliveryNotifier;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Tampilkan halaman checkout untuk satu produk digital (form data customer).
     */
    public function show(string $code): View
    {
        $product = CpDigitalProduct::active()->where('code', $code)->firstOrFail();

        abort_if($product->billing_mode !== 'midtrans', 404, 'Produk ini belum dapat dibeli.');

        $affiliateService = app(\App\Services\AffiliateService::class);
        $ref = request('ref');

        return view('Companyprofile.checkout', [
            'active'  => 'produk',
            'product' => $product,
            'referralEnabled' => $affiliateService->enabled() && $affiliateService->isEligibleProduct($product),
            'referralDiscount' => $affiliateService->discountAmount(),
            'prefillReferral' => is_string($ref) ? strtoupper(trim($ref)) : '',
        ]);
    }

    /**
     * Buat order + Midtrans Snap, redirect user ke payment page.
     *
     * Mendukung dua mode:
     * 1) Produk digital baru (param: product) — utama
     * 2) Plan legacy YFD bot SaaS (param: plan)  — backward-compat
     */
    public function store(Request $request, MidtransService $midtransService)
    {
        // ── Mode 1: produk digital ──────────────────────────────────────────
        if ($request->filled('product')) {
            return $this->checkoutDigitalProduct($request, $midtransService);
        }

        // ── Mode 2: legacy plan checkout (backward compat) ──────────────────
        return $this->checkoutLegacyPlan($request, $midtransService);
    }

    private function checkoutDigitalProduct(Request $request, MidtransService $midtransService)
    {
        $validated = $request->validate([
            'product'    => ['required', 'string', 'exists:cp_digital_products,code'],
            'full_name'  => ['required', 'string', 'max:120'],
            'email'      => ['required', 'email', 'max:190'],
            'phone'      => ['required', 'string', 'max:32'],
            'telegram_username' => ['nullable', 'string', 'max:120'],
            'referral_code' => ['nullable', 'string', 'max:32'],
        ]);

        $product = CpDigitalProduct::active()->where('code', $validated['product'])->firstOrFail();
        abort_if($product->billing_mode !== 'midtrans', 422, 'Produk ini belum dapat dibeli.');

        $baseAmount = $product->effective_price;
        abort_if($baseAmount <= 0, 422, 'Harga produk belum diatur.');

        $affiliateService = app(\App\Services\AffiliateService::class);
        $pricing = $affiliateService->applyCheckoutDiscount(
            $product,
            $baseAmount,
            $validated['referral_code'] ?? $request->query('ref'),
            $validated['email'],
        );
        $finalAmount = $pricing['final_amount'];

        $order = Order::create([
            'order_code'         => 'YFD-'.Str::upper(Str::random(10)),
            'full_name'          => $validated['full_name'],
            'email'              => $validated['email'],
            'phone'              => $this->normalizePhone($validated['phone']),
            'telegram_username'  => $validated['telegram_username'] ?? null,
            'referral_code'      => $pricing['referral_code'],
            'affiliate_id'       => $pricing['affiliate']?->id,
            'plan'               => $product->code,           // simpan kode juga untuk kompatibilitas
            'digital_product_id' => $product->id,
            'product_name'       => $product->name,
            'amount'             => $finalAmount,
            'original_price'     => (int) $product->price,
            'discount_amount'    => $pricing['discount_amount'],
            'referral_discount'  => $pricing['referral_discount'],
            'currency'           => $product->currency ?? 'IDR',
            'status'             => 'pending',
            'payment_gateway'    => 'midtrans',
        ]);

        try {
            $payment = $midtransService->createSnapTransaction([
                'order_id'     => $order->order_code,
                'gross_amount' => $order->amount,
                'full_name'    => $order->full_name,
                'email'        => $order->email,
                'phone'        => $order->phone,
                'item_details' => [[
                    'id'       => $product->code,
                    'price'    => $finalAmount,
                    'quantity' => 1,
                    'name'     => Str::limit($product->name, 50),
                ]],
            ]);
            $order->payment_token = $payment['token'] ?? null;
            $order->payment_url   = $payment['redirect_url'] ?? null;
            $order->save();
        } catch (\Throwable $e) {
            Log::warning('Midtrans Snap gagal (produk digital)', [
                'order_code' => $order->order_code,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('company.produk')
                ->with('success', "Order {$order->order_code} dibuat, namun gagal membuat link bayar otomatis. Tim YFD akan menghubungi Anda via WA. Cek konfigurasi Midtrans.");
        }

        if ($order->payment_url) {
            return redirect()->away($order->payment_url);
        }

        return redirect()
            ->route('company.produk')
            ->with('success', "Order {$order->order_code} dibuat. Lanjutkan pembayaran di channel Midtrans Anda.");
    }

    private function checkoutLegacyPlan(Request $request, MidtransService $midtransService)
    {
        $validated = $request->validate([
            'full_name'         => ['required', 'string', 'max:120'],
            'email'             => ['required', 'email', 'max:190'],
            'phone'             => ['required', 'string', 'max:32'],
            'telegram_username' => ['nullable', 'string', 'max:120'],
            'plan'              => ['required', 'in:lite,pro,ecosystem'],
        ]);

        $amountMap = [
            'lite'      => 2000000,
            'pro'       => 4500000,
            'ecosystem' => 8500000,
        ];

        $order = Order::create([
            'order_code'        => 'ORD-'.Str::upper(Str::random(10)),
            'full_name'         => $validated['full_name'],
            'email'             => $validated['email'],
            'phone'             => $this->normalizePhone($validated['phone']),
            'telegram_username' => $validated['telegram_username'] ?? null,
            'plan'              => $validated['plan'],
            'amount'            => $amountMap[$validated['plan']],
            'original_price'    => $amountMap[$validated['plan']],
            'currency'          => 'IDR',
            'status'            => 'pending',
            'payment_gateway'   => 'midtrans',
        ]);

        try {
            $payment = $midtransService->createSnapTransaction([
                'order_id'     => $order->order_code,
                'gross_amount' => $order->amount,
                'full_name'    => $order->full_name,
                'email'        => $order->email,
                'phone'        => $order->phone,
            ]);
            $order->payment_token = $payment['token'] ?? null;
            $order->payment_url   = $payment['redirect_url'] ?? null;
            $order->save();
        } catch (\Throwable $e) {
            Log::warning('Midtrans Snap gagal (legacy plan)', [
                'order_code' => $order->order_code,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('landing')
                ->with('success', "Order {$order->order_code} dibuat, namun gagal membuat link bayar otomatis. Cek konfigurasi Midtrans.");
        }

        if ($order->payment_url) {
            return redirect()->away($order->payment_url);
        }

        return redirect()
            ->route('landing')
            ->with('success', "Order {$order->order_code} dibuat. Lanjutkan pembayaran di channel Midtrans Anda.");
    }

    /**
     * Halaman setelah pembayaran (redirect dari Midtrans).
     */
    public function finish(Request $request, MidtransPaymentSyncService $paymentSync): View
    {
        $orderCode = $request->query('order_id');
            $order = $orderCode
            ? Order::with(['license', 'consultationSlot'])->where('order_code', $orderCode)->first()
            : null;

        if ($order !== null && $order->status === 'pending') {
            $paymentSync->syncOrderFromApi($order);
            $order->refresh()->load(['license', 'consultationSlot']);
        }

        $notifier = app(OrderDeliveryNotifier::class);
        $channels = $notifier->enabledChannels();
        $orderContext = $order ? app(\App\Services\PortalOnboardingService::class)->orderDeliveryContext($order) : null;
        $consultationWaUrl = null;
        if ($order && $order->isConsultationOrder() && $order->consultationSlot) {
            $consultationWaUrl = app(\App\Services\ConsultationSlotService::class)
                ->paidGuestWhatsAppUrl($order->consultationSlot);
        }

        return view('Companyprofile.checkout-finish', [
            'active' => 'produk',
            'order'  => $order,
            'orderContext' => $orderContext,
            'deliveryChannelLabel' => $notifier->primaryChannelLabel(),
            'deliveryViaEmail' => in_array('email', $channels, true),
            'consultationWaUrl' => $consultationWaUrl,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = PhoneNumber::normalizeIndonesia($phone);

        if (! PhoneNumber::isValidIndonesiaMobile($normalized)) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx atau 628xxxxxxxxxx.',
            ]);
        }

        return $normalized;
    }
}
