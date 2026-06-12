<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\UserSheet;
use App\Services\GoogleDriveSheetProvisioner;
use App\Services\GoogleSheetPrivacyService;
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

    public function handle(GoogleDriveSheetProvisioner $provisioner): void
    {
        $order = Order::with('license')->find($this->orderId);
        if (! $order || $order->status !== 'paid' || ! $order->license) {
            return;
        }

        $deliveryAlreadySent = $order->purchase_delivery_sent_at !== null;

        // WA/email dulu — jangan ditahan menunggu Google Sheet (sering gagal/lambat di VPS).
        if (! $deliveryAlreadySent) {
            $order->load('license');
            app(OrderDeliveryNotifier::class)->send($order);
            Order::whereKey($order->id)->update(['purchase_delivery_sent_at' => now()]);
        }

        $this->provisionSheetBestEffort($order->fresh(), $provisioner);
    }

    private function provisionSheetBestEffort(Order $order, GoogleDriveSheetProvisioner $provisioner): void
    {
        if (! $provisioner->isConfigured()) {
            return;
        }

        $privacy = app(GoogleSheetPrivacyService::class);

        try {
            if ($order->spreadsheet_id === null) {
                $result = $provisioner->copyTemplateForOrder($order);
                $order->spreadsheet_id = $result['id'];
                $order->spreadsheet_url = $result['url'];
                $order->save();
                $order->refresh();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal duplikasi Google Sheet untuk order '.$order->order_code, [
                'exception' => $e->getMessage(),
            ]);
        }

        if ($order->spreadsheet_id === null) {
            return;
        }

        try {
            $diag = $privacy->ensureOrderAccessible($order->fresh(), (string) $order->spreadsheet_id);
            if (! $diag['ok']) {
                Log::warning('Google Sheet ada tetapi izin belum lengkap untuk '.$order->order_code, [
                    'message' => $diag['message'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Gagal terapkan izin Google Sheet untuk order '.$order->order_code, [
                'exception' => $e->getMessage(),
            ]);
        }

        UserSheet::syncFromOrder($order->fresh(['license']));
    }
}
