<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\UserSheet;
use App\Services\GoogleDriveSheetProvisioner;
use App\Services\GoogleSheetPrivacyService;
use App\Services\OrderDeliveryMailer;
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
        $sheetRequired = $provisioner->isConfigured();
        $privacy = app(GoogleSheetPrivacyService::class);

        if ($sheetRequired && $order->spreadsheet_id === null) {
            $result = $provisioner->copyTemplateForOrder($order);
            $order->spreadsheet_id = $result['id'];
            $order->spreadsheet_url = $result['url'];
            $order->save();
        }

        // Sheet sudah ada di DB tetapi izin sering gagal diam-diam — selalu perbaiki akses.
        if ($sheetRequired && $order->spreadsheet_id !== null) {
            $diag = $privacy->ensureOrderAccessible($order->fresh(), (string) $order->spreadsheet_id);
            if (! $diag['ok']) {
                throw new \RuntimeException(
                    'Google Sheet ada tetapi tidak bisa diakses untuk '.$order->order_code.': '.$diag['message']
                );
            }
            UserSheet::syncFromOrder($order->fresh(['license']));
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
            app(OrderDeliveryMailer::class)->send($order);
        } catch (\Throwable $e) {
            Log::warning('Email pengiriman order lunas gagal (lisensi tetap di halaman checkout & DB)', [
                'order_code' => $order->order_code,
                'exception'  => $e->getMessage(),
            ]);
        }

        UserSheet::syncFromOrder($order->fresh(['license']));

        Order::whereKey($order->id)->update(['purchase_delivery_sent_at' => now()]);
    }
}
