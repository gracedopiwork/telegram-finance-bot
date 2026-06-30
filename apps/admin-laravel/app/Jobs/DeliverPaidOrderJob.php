<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderDeliveryNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverPaidOrderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(public int $orderId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300, 600];
    }

    public function handle(OrderDeliveryNotifier $notifier): void
    {
        $order = Order::with('license')->find($this->orderId);
        if (! $order || $order->status !== 'paid' || ! $order->license) {
            return;
        }

        $deliveryAlreadySent = $order->purchase_delivery_sent_at !== null;

        if (! $deliveryAlreadySent) {
            try {
                $notifier->send($order);
                Order::whereKey($order->id)->update(['purchase_delivery_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Pengiriman ringkasan order gagal', [
                    'order_code' => $order->order_code,
                    'exception' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }
}
