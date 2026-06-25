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

    public function handle(
        GoogleDriveSheetProvisioner $provisioner,
        OrderDeliveryNotifier $notifier,
    ): void {
        $order = Order::with('license')->find($this->orderId);
        if (! $order || $order->status !== 'paid' || ! $order->license) {
            return;
        }

        $deliveryAlreadySent = $order->purchase_delivery_sent_at !== null;

        // Sheet dulu — jangan ditahan menunggu WA (Fonnte sering belum aktif / gagal).
        $this->provisionSheetBestEffort($order, $provisioner);
        $order = $order->fresh(['license']);

        if (! $deliveryAlreadySent) {
            try {
                $notifier->send($order);
                Order::whereKey($order->id)->update(['purchase_delivery_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Pengiriman WA/email gagal — Google Sheet tetap tersedia di checkout', [
                    'order_code' => $order->order_code,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    private function provisionSheetBestEffort(Order $order, GoogleDriveSheetProvisioner $provisioner): void
    {
        if (! $provisioner->isConfigured()) {
            Log::error('Google Sheet TIDAK dibuat — konfigurasi .env tidak lengkap', [
                'order_code' => $order->order_code,
                'GOOGLE_USER_SHEET_TEMPLATE_ID' => (string) config('services.google.user_sheet_template_id', '') ?: '(kosong)',
                'GOOGLE_SERVICE_ACCOUNT_JSON' => (string) config('services.google.service_account_json', '') ?: '(kosong)',
                'hint' => 'Jalankan: php artisan google:sheet-setup',
            ]);

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
