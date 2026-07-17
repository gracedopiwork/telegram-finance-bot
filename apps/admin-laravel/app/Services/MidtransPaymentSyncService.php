<?php

namespace App\Services;

use App\Jobs\DeliverPaidOrderJob;
use App\Models\License;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Services\AffiliateService;
use App\Services\LicenseProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransPaymentSyncService
{
    public function __construct(private MidtransService $midtrans) {}

    /**
     * Ambil status terbaru dari Midtrans API lalu terapkan ke order lokal.
     *
     * @return array{synced: bool, message: string, transaction_status: ?string}
     */
    public function syncOrderFromApi(Order $order): array
    {
        $payload = $this->midtrans->fetchTransactionStatus($order->order_code);
        if ($payload === null) {
            return [
                'synced' => false,
                'message' => 'Transaksi tidak ditemukan di Midtrans (cek MIDTRANS_IS_PRODUCTION & server key).',
                'transaction_status' => null,
            ];
        }

        $wasPaid = $order->status === 'paid';
        $this->applyNotificationPayload($order, $payload, 'status_api');

        $order->refresh();
        $status = (string) ($payload['transaction_status'] ?? 'unknown');

        if ($order->status === 'paid' && ! $wasPaid) {
            DeliverPaidOrderJob::dispatchSync($order->id);
        }

        return [
            'synced' => true,
            'message' => "Status Midtrans: {$status} → order {$order->status}.",
            'transaction_status' => $status,
        ];
    }

    /**
     * Proses payload HTTP notification dari Midtrans (webhook).
     */
    public function handleWebhook(array $payload): void
    {
        if (! $this->midtrans->verifyNotificationSignature($payload)) {
            Log::warning('Midtrans webhook: signature tidak valid', [
                'order_id' => $payload['order_id'] ?? null,
                'status_code' => $payload['status_code'] ?? null,
            ]);

            throw new \RuntimeException('Invalid signature');
        }

        $order = Order::with('digitalProduct')
            ->where('order_code', $payload['order_id'] ?? '')
            ->first();

        if ($order === null) {
            throw new \RuntimeException('Order not found');
        }

        $alreadyPaid = $order->status === 'paid';
        $this->applyNotificationPayload($order, $payload, 'webhook');

        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $isPaid = $this->isPaidStatus($transactionStatus);

        if ($isPaid && ! $alreadyPaid) {
            DeliverPaidOrderJob::dispatchSync($order->id);
        }
    }

    private function applyNotificationPayload(Order $order, array $payload, string $source): void
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? 'unknown');

        PaymentEvent::create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'event_type' => $transactionStatus,
            'payload_json' => array_merge($payload, ['_source' => $source]),
            'created_at' => now(),
        ]);

        $isPaid = $this->isPaidStatus($transactionStatus);
        $isFailed = in_array($transactionStatus, ['deny', 'cancel', 'expire'], true);

        DB::transaction(function () use ($order, $payload, $isPaid, $isFailed): void {
            $order->payment_reference = $payload['transaction_id'] ?? $order->payment_reference;

            if ($isPaid) {
                $order->loadMissing('digitalProduct');
                $provisioning = app(LicenseProvisioningService::class);

                $license = $order->license_id ? License::find($order->license_id) : null;
                if ($license === null) {
                    $license = $provisioning->resolveLicenseForPaidOrder($order);
                } else {
                    $provisioning->syncEntitlementsOntoLicense($order, $license);
                }

                $order->license_id = $license->id;
                $order->status = 'paid';
                $order->paid_at = $order->paid_at ?? now();

                app(AffiliateService::class)->creditCommissionForPaidOrder($order);
            } elseif ($isFailed) {
                $order->status = 'failed';
            }

            $order->save();
        });
    }

    private function isPaidStatus(string $transactionStatus): bool
    {
        return in_array($transactionStatus, ['capture', 'settlement'], true);
    }

    private function resolveLicenseForPaidOrder(Order $order): License
    {
        return app(LicenseProvisioningService::class)->resolveLicenseForPaidOrder($order);
    }
}
