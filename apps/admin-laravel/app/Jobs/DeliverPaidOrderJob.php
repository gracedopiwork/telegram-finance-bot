<?php

namespace App\Jobs;

use App\Mail\PaidOrderDeliveredMail;
use App\Models\Order;
use App\Services\GoogleDriveSheetProvisioner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    public function handle(GoogleDriveSheetProvisioner $provisioner): void
    {
        $order = Order::with('license')->find($this->orderId);
        if (! $order || $order->status !== 'paid' || ! $order->license) {
            return;
        }

        $deliveryAlreadySent = $order->purchase_delivery_sent_at !== null;

        if ($deliveryAlreadySent && $order->spreadsheet_id !== null) {
            return;
        }

        $sheetRequired = $provisioner->isConfigured();

        if ($sheetRequired && $order->spreadsheet_id === null) {
            $result = $provisioner->copyTemplateForOrder($order);
            $order->spreadsheet_id = $result['id'];
            $order->spreadsheet_url = $result['url'];
            $order->save();
        }

        if ($deliveryAlreadySent) {
            return;
        }

        $order->refresh();

        if ($sheetRequired && $order->spreadsheet_id === null) {
            Log::error('Gagal duplikasi Google Sheet untuk order '.$order->order_code, [
                'exception' => 'spreadsheet_id masih kosong setelah copy',
            ]);
            throw new \RuntimeException('Google Sheet belum terbuat untuk order '.$order->order_code);
        }

        $order->load('license');

        try {
            Mail::to($order->email)->send(new PaidOrderDeliveredMail($order));
        } catch (\Throwable $e) {
            Log::warning('Email pengiriman order lunas gagal (lisensi tetap di halaman checkout & DB)', [
                'order_code' => $order->order_code,
                'exception'  => $e->getMessage(),
            ]);
        }

        Order::whereKey($order->id)->update(['purchase_delivery_sent_at' => now()]);
    }
}
