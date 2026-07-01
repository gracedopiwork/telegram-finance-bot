<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderDeliveryNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dijalankan sinkron (tanpa queue worker) agar email/WA terkirim langsung setelah webhook Midtrans.
 */
class DeliverPaidOrderJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $orderId) {}

    public function handle(OrderDeliveryNotifier $notifier): void
    {
        $order = Order::with(['license', 'digitalProduct'])->find($this->orderId);
        if (! $order || $order->status !== 'paid' || ! $order->license) {
            return;
        }

        if ($order->purchase_delivery_sent_at !== null) {
            return;
        }

        try {
            $succeeded = $notifier->send($order);
            if ($succeeded !== []) {
                Order::whereKey($order->id)->update(['purchase_delivery_sent_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Pengiriman ringkasan order gagal', [
                'order_code' => $order->order_code,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
