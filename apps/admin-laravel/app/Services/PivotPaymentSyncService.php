<?php

namespace App\Services;

use App\Jobs\DeliverPaidOrderJob;
use App\Models\License;
use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PivotPaymentSyncService
{
    public function __construct(private PivotService $pivot) {}

    /**
     * @return array{synced: bool, message: string, transaction_status: ?string}
     */
    public function syncOrderFromApi(Order $order): array
    {
        $payload = $this->pivot->fetchPaymentByClientReferenceId($order->order_code);
        if ($payload === null) {
            return [
                'synced' => false,
                'message' => 'Transaksi tidak ditemukan di Pivot (cek PIVOT_IS_PRODUCTION, credentials, & IP whitelist).',
                'transaction_status' => null,
            ];
        }

        $wasPaid = $order->status === 'paid';
        $this->applySessionPayload($order, $payload, 'status_api');

        $order->refresh();
        $status = strtoupper((string) ($payload['status'] ?? 'unknown'));

        if ($order->status === 'paid' && ! $wasPaid) {
            DeliverPaidOrderJob::dispatchSync($order->id);
        }

        return [
            'synced' => true,
            'message' => "Status Pivot: {$status} → order {$order->status}.",
            'transaction_status' => $status,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void
    {
        $event = $this->extractEventName($payload);
        $session = $this->extractSessionPayload($payload);
        $orderCode = (string) (
            $session['clientReferenceId']
            ?? $session['client_reference_id']
            ?? $payload['clientReferenceId']
            ?? $payload['client_reference_id']
            ?? ''
        );

        if ($orderCode === '') {
            Log::warning('Pivot webhook: clientReferenceId kosong', ['event' => $event]);
            throw new \RuntimeException('Order reference missing');
        }

        $order = Order::with('digitalProduct')
            ->where('order_code', $orderCode)
            ->first();

        if ($order === null) {
            throw new \RuntimeException('Order not found');
        }

        $alreadyPaid = $order->status === 'paid';
        $merged = array_merge($session, [
            '_event' => $event,
            'status' => $session['status'] ?? $this->statusFromEvent($event),
        ]);

        $this->applySessionPayload($order, $merged, 'webhook');

        $isPaid = $this->isPaidEvent($event) || $this->isPaidStatus((string) ($merged['status'] ?? ''));

        if ($isPaid && ! $alreadyPaid) {
            DeliverPaidOrderJob::dispatchSync($order->id);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applySessionPayload(Order $order, array $payload, string $source): void
    {
        $event = (string) ($payload['_event'] ?? '');
        $status = strtoupper((string) ($payload['status'] ?? 'UNKNOWN'));
        $eventType = $event !== '' ? $event : $status;

        PaymentEvent::create([
            'order_id' => $order->id,
            'provider' => 'pivot',
            'event_type' => Str::limit($eventType, 80, ''),
            'payload_json' => array_merge($payload, ['_source' => $source]),
            'created_at' => now(),
        ]);

        $isPaid = $this->isPaidEvent($event) || $this->isPaidStatus($status);
        $isFailed = $this->isFailedEvent($event) || $this->isFailedStatus($status);

        $reference = (string) (
            $payload['id']
            ?? data_get($payload, 'chargeDetails.0.id')
            ?? $order->payment_reference
            ?? ''
        );

        DB::transaction(function () use ($order, $reference, $isPaid, $isFailed): void {
            if ($reference !== '') {
                $order->payment_reference = $reference;
            }

            if ($isPaid) {
                if ($order->isConsultationOrder()) {
                    $order->status = 'paid';
                    $order->paid_at = $order->paid_at ?? now();
                } else {
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
                }
            } elseif ($isFailed) {
                $order->status = 'failed';
            }

            $order->save();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractEventName(array $payload): string
    {
        foreach (['event', 'eventName', 'event_name', 'eventType', 'event_type', 'type'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return strtoupper($value);
            }
        }

        $nested = $payload['data'] ?? null;
        if (is_array($nested)) {
            foreach (['event', 'eventName', 'event_name'] as $key) {
                $value = $nested[$key] ?? null;
                if (is_string($value) && $value !== '') {
                    return strtoupper($value);
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractSessionPayload(array $payload): array
    {
        $data = $payload['data'] ?? null;
        if (is_array($data)) {
            if (isset($data['clientReferenceId']) || isset($data['status']) || isset($data['id'])) {
                return $data;
            }
            if (isset($data['paymentSession']) && is_array($data['paymentSession'])) {
                return $data['paymentSession'];
            }
            if (array_is_list($data) && isset($data[0]) && is_array($data[0])) {
                return $data[0];
            }
        }

        return $payload;
    }

    private function statusFromEvent(string $event): string
    {
        if ($this->isPaidEvent($event)) {
            return 'PAID';
        }
        if ($this->isFailedEvent($event)) {
            return 'CANCELLED';
        }

        return 'PROCESSING';
    }

    private function isPaidEvent(string $event): bool
    {
        return in_array(strtoupper($event), [
            'PAYMENT.PAID',
            'PAYMENT.SUCCESS',
            'CHARGE.SUCCESS',
        ], true);
    }

    private function isFailedEvent(string $event): bool
    {
        return in_array(strtoupper($event), [
            'PAYMENT.CANCELLED',
            'PAYMENT.CANCELED',
            'PAYMENT.EXPIRED',
            'PAYMENT.FAILED',
        ], true);
    }

    private function isPaidStatus(string $status): bool
    {
        return in_array(strtoupper($status), ['PAID', 'SUCCESS', 'SETTLED', 'CAPTURED'], true);
    }

    private function isFailedStatus(string $status): bool
    {
        return in_array(strtoupper($status), [
            'CANCELLED',
            'CANCELED',
            'EXPIRED',
            'FAILED',
            'VOID',
        ], true);
    }
}
