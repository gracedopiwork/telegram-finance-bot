<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverPaidOrderJob;
use App\Models\CpDigitalProduct;
use App\Models\License;
use App\Models\Order;
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

    public function show(Order $order)
    {
        $order->load(['digitalProduct', 'license', 'paymentEvents']);

        return view('admin.orders.show', [
            'order' => $order,
            'telegramBotUrl' => TelegramBotUrl::resolve(),
            'deliveryChannelLabel' => app(OrderDeliveryNotifier::class)->primaryChannelLabel(),
        ]);
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
            // Auto-generate license jika belum punya
            if (! $order->license_id) {
                $license = License::create([
                    'license_key'  => $this->generateLicenseKey(),
                    'plan'         => $order->plan ?? ($order->digitalProduct?->code ?? 'manual'),
                    'status'       => 'active',
                    'expires_at'   => now()->addYear(),
                    'max_accounts' => 1,
                ]);
                $order->license_id = $license->id;
            }
        }

        if ($data['status'] === 'failed') {
            $order->paid_at = null;
        }

        $order->save();

        if ($data['status'] === 'paid' && ! $wasPaid) {
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

    public function destroy(Order $order)
    {
        $licenseId = $order->license_id;
        $code = $order->order_code;

        DB::transaction(function () use ($order, $licenseId): void {
            $order->delete();

            if ($licenseId !== null && ! Order::query()->where('license_id', $licenseId)->exists()) {
                License::query()->whereKey($licenseId)->delete();
            }
        });

        return redirect()->route('admin.orders.index')
            ->with('success', "Order {$code} dihapus. Lisensi terkait ikut dihapus jika tidak dipakai order lain, beserta data transaksi/baseline user jika sudah orphan.");
    }

    private function generateLicenseKey(): string
    {
        return 'YFD-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
    }
}
