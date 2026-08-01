<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverPaidOrderJob;
use App\Models\CpDigitalProduct;
use App\Models\License;
use App\Models\Order;
use App\Services\CustomerDataPurgeService;
use App\Services\MidtransPaymentSyncService;
use App\Services\MidtransService;
use App\Services\LicenseEntitlementService;
use App\Services\LicenseProvisioningService;
use App\Services\OrderDeliveryNotifier;
use App\Support\TelegramBotUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $q = Order::query()->with(['digitalProduct', 'license']);

        // Filter status
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        // Filter produk digital
        if ($productId = $request->query('product_id')) {
            $q->where('digital_product_id', $productId);
        }

        // Search
        if ($search = trim((string) $request->query('search'))) {
            $q->where(function ($w) use ($search) {
                $w->where('order_code', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('telegram_username', 'like', "%{$search}%");
            });
        }

        $orders = $q->latest()->paginate(25)->withQueryString();

        // Stat ringkas (semua order, tidak ikut filter — kecuali by product)
        $statBase = Order::query();
        if ($productId) {
            $statBase->where('digital_product_id', $productId);
        }
        $stats = [
            'total'   => (clone $statBase)->count(),
            'paid'    => (clone $statBase)->where('status', 'paid')->count(),
            'pending' => (clone $statBase)->where('status', 'pending')->count(),
            'failed'  => (clone $statBase)->where('status', 'failed')->count(),
            'revenue' => (clone $statBase)->where('status', 'paid')->sum('amount'),
        ];

        $products = CpDigitalProduct::orderBy('name')->get(['id', 'name']);

        return view('admin.orders.index', compact('orders', 'stats', 'products'));
    }

    public function create()
    {
        $products = CpDigitalProduct::query()
            ->orderByDesc('is_active')
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'period', 'price', 'discount_price', 'is_active']);

        return view('admin.orders.create', [
            'products' => $products,
            'defaultNote' => 'Dibuat admin — bukan bayar',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:32',
            'telegram_username' => 'nullable|string|max:120',
            'digital_product_id' => 'required|exists:cp_digital_products,id',
            'referral_code' => 'nullable|string|max:32',
            'admin_note' => 'nullable|string|max:2000',
            'send_delivery' => 'nullable|boolean',
        ]);

        $product = CpDigitalProduct::query()->findOrFail((int) $data['digital_product_id']);
        $note = trim((string) ($data['admin_note'] ?? ''));
        if ($note === '') {
            $note = 'Dibuat admin — bukan bayar';
        }

        $preferredCode = app(\App\Services\AffiliateService::class)
            ->normalizeReferralCode($data['referral_code'] ?? null);
        if ($preferredCode !== null) {
            app(\App\Services\AffiliateService::class)->assertReferralCodeAvailable($preferredCode);
        }

        $order = DB::transaction(function () use ($data, $product, $note, $preferredCode) {
            $order = Order::query()->create([
                'order_code' => 'YFD-ADM-'.Str::upper(Str::random(8)),
                'full_name' => trim($data['full_name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => isset($data['phone']) ? trim((string) $data['phone']) : null,
                'telegram_username' => isset($data['telegram_username'])
                    ? ltrim(trim((string) $data['telegram_username']), '@')
                    : null,
                'plan' => (string) $product->code,
                'digital_product_id' => $product->id,
                'product_name' => $product->name,
                'amount' => 0,
                'original_price' => (int) ($product->discount_price ?: $product->price ?: 0),
                'discount_amount' => (int) ($product->discount_price ?: $product->price ?: 0),
                'currency' => 'IDR',
                'status' => 'paid',
                'payment_gateway' => 'admin',
                'payment_reference' => 'admin-complimentary',
                'admin_note' => $note,
                'paid_at' => now(),
            ]);

            $license = app(LicenseProvisioningService::class)->resolveLicenseForPaidOrder($order);
            $order->license_id = $license->id;
            $order->save();

            app(\App\Services\AffiliateService::class)->ensureForPortalUser(
                (string) $order->email,
                $order->full_name,
                $order->license_id,
                $preferredCode,
            );

            return $order->fresh(['digitalProduct', 'license']);
        });

        if ($request->boolean('send_delivery')) {
            DeliverPaidOrderJob::dispatchSync($order->id);
        }

        $affiliate = app(\App\Services\AffiliateService::class)->ensureForPortalUser(
            (string) $order->email,
            $order->full_name,
            $order->license_id,
            $preferredCode,
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with(
                'success',
                'User gratis dibuat. Order '.$order->order_code
                .' · Lisensi: '.($order->license?->license_key ?? '—')
                .' · Kode affiliate: '.$affiliate->referral_code
            );
    }

    public function show(Order $order)
    {
        $order->load(['digitalProduct', 'license', 'paymentEvents']);

        $entitlements = app(LicenseEntitlementService::class);
        $licenseEntitlementLabel = $order->license
            ? $entitlements->licenseEntitlementLabel($order->license)
            : null;

        $buyerAffiliate = null;
        if (filled($order->email)) {
            $buyerAffiliate = app(\App\Services\AffiliateService::class)->ensureForPortalUser(
                (string) $order->email,
                $order->full_name,
                $order->license_id,
            );
        }

        return view('admin.orders.show', [
            'order' => $order,
            'licenseEntitlementLabel' => $licenseEntitlementLabel,
            'buyerAffiliate' => $buyerAffiliate,
            'telegramBotUrl' => TelegramBotUrl::resolve(),
            'deliveryChannelLabel' => app(OrderDeliveryNotifier::class)->primaryChannelLabel(),
            'midtransNotificationUrl' => app(MidtransService::class)->notificationUrl(),
        ]);
    }

    public function syncPayment(Order $order, MidtransPaymentSyncService $sync)
    {
        if ($order->status === 'paid') {
            $order->loadMissing(['digitalProduct', 'license']);
            $provisioning = app(LicenseProvisioningService::class);

            if (! $order->license_id) {
                $license = $provisioning->resolveLicenseForPaidOrder($order);
                $order->license_id = $license->id;
                $order->save();
            } elseif ($order->license) {
                $provisioning->syncEntitlementsOntoLicense($order, $order->license);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Order sudah lunas. Lisensi & hak akses disinkronkan ulang.');
        }

        $result = $sync->syncOrderFromApi($order);

        if (! $result['synced']) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', $result['message']);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', $result['message']);
    }

    /**
     * Ubah status manual (kalau webhook gagal / setelah verifikasi manual).
     * Bisa juga generate license kalau di-mark-paid manual.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,paid,failed',
        ]);

        $wasPaid = $order->status === 'paid';

        $order->status = $data['status'];

        if ($data['status'] === 'paid') {
            $order->paid_at = $order->paid_at ?? now();
            $order->loadMissing(['digitalProduct', 'license']);
            // Auto-generate license jika belum punya
            if (! $order->license_id) {
                $license = app(LicenseProvisioningService::class)->resolveLicenseForPaidOrder($order);
                $order->license_id = $license->id;
            } elseif ($order->license) {
                app(LicenseProvisioningService::class)->syncEntitlementsOntoLicense($order, $order->license);
            }
        }

        if ($data['status'] === 'failed') {
            $order->paid_at = null;
        }

        $order->save();

        if ($data['status'] === 'paid' && ! $wasPaid) {
            if (! $order->isAdminComplimentary()) {
                app(\App\Services\AffiliateService::class)->creditCommissionForPaidOrder($order->fresh(['digitalProduct', 'affiliate']));
            }
            DeliverPaidOrderJob::dispatchSync($order->id);
        }

        return redirect()->route('admin.orders.show', $order)
                         ->with('success', "Status order {$order->order_code} di-set menjadi {$order->status}.");
    }

    public function resendDelivery(Order $order, OrderDeliveryNotifier $notifier)
    {
        if ($order->status !== 'paid' || ! $order->license) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Hanya order lunas dengan lisensi yang bisa dikirim ringkasan.');
        }

        $channels = $notifier->enabledChannels();
        if (in_array('wa', $channels, true) && ! TelegramBotUrl::resolve()) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Tautan bot belum di-set. Isi TELEGRAM_BOT_USERNAME di .env atau Site Settings → Integrasi Bot.');
        }

        try {
            $notifier->send($order);
        } catch (\Throwable $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Gagal kirim: '.$e->getMessage());
        }

        $order->update(['purchase_delivery_sent_at' => now()]);

        $destination = in_array('email', $channels, true) && in_array('wa', $channels, true)
            ? 'WhatsApp & Email'
            : (in_array('wa', $channels, true) ? 'WhatsApp '.$order->phone : $order->email);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Ringkasan terkirim ke '.$destination.'.');
    }

    public function resendDeliveryEmail(Order $order, OrderDeliveryNotifier $notifier)
    {
        if ($order->status !== 'paid' || ! $order->license) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Hanya order lunas dengan lisensi yang bisa dikirim email.');
        }

        try {
            $notifier->sendEmailOnly($order);
        } catch (\Throwable $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Gagal kirim email: '.$e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Email terkirim ke '.$order->email.'.');
    }

    public function destroy(Order $order, CustomerDataPurgeService $purge)
    {
        $licenseId = $order->license_id;
        $code = $order->order_code;
        $email = $order->email;
        $telegramUserIds = $purge->collectTelegramUserIdsForEmail($email);

        DB::transaction(function () use ($order, $licenseId): void {
            $order->delete();

            if ($licenseId !== null && ! Order::query()->where('license_id', $licenseId)->exists()) {
                License::query()->whereKey($licenseId)->delete();
            }
        });

        $purgedRows = 0;
        if (! Order::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->exists()) {
            $purgedRows = $purge->purgeFinanceDataForTelegramUserIds($telegramUserIds);
        }

        $message = "Order {$code} dihapus.";
        if ($purgedRows > 0) {
            $message .= " Data baseline & transaksi untuk email ini ikut dihapus ({$purgedRows} baris).";
        } else {
            $message .= ' Lisensi terkait ikut dihapus jika tidak dipakai order lain.';
        }

        return redirect()->route('admin.orders.index')->with('success', $message);
    }

    public function purgeCustomerData(Order $order, CustomerDataPurgeService $purge)
    {
        $telegramUserIds = $purge->collectTelegramUserIdsForEmail($order->email);
        $deleted = $purge->purgeFinanceDataForTelegramUserIds($telegramUserIds);

        if ($deleted === 0 && $telegramUserIds === []) {
            return redirect()->route('admin.orders.show', $order)
                ->with('warning', 'Tidak ada data baseline/transaksi yang terhubung ke email '.$order->email.' (belum ada aktivasi bot).');
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Data baseline & transaksi untuk {$order->email} dihapus ({$deleted} baris). Order & lisensi tetap ada.");
    }

    private function generateLicenseKey(): string
    {
        return 'YFD-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
    }
}
