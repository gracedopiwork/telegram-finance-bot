<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverPaidOrderJob;
use App\Models\CpDigitalProduct;
use App\Models\License;
use App\Models\Order;
use App\Services\GoogleSheetPrivacyService;
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
        return view('admin.orders.show', compact('order'));
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
            DB::afterCommit(function () use ($order): void {
                DeliverPaidOrderJob::dispatch($order->id);
            });
        }

        return redirect()->route('admin.orders.show', $order)
                         ->with('success', "Status order {$order->order_code} di-set menjadi {$order->status}.");
    }

    public function provisionSheet(Order $order)
    {
        if ($order->status !== 'paid') {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Hanya order lunas yang bisa dibuatkan spreadsheet.');
        }

        if ($order->spreadsheet_id) {
            try {
                app(GoogleSheetPrivacyService::class)->configureSpreadsheetForOrder(
                    (string) $order->spreadsheet_id,
                    $order
                );
            } catch (\Throwable $e) {
                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Gagal terapkan privasi/izin: '.$e->getMessage());
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Privasi & izin sheet diperbarui. Akses dibagikan ke email checkout: '.$order->email);
        }

        DeliverPaidOrderJob::dispatch($order->id);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Job pembuatan Google Sheet dimasukkan antrian. Refresh halaman ini setelah beberapa detik (pastikan queue:work berjalan).');
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
            ->with('success', "Order {$code} dihapus. Lisensi terkait ikut dihapus jika tidak dipakai order lain.");
    }

    private function generateLicenseKey(): string
    {
        return 'YFD-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
    }
}
