<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function midtrans(Request $request)
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') {
            return response()->json(['message' => 'Midtrans key not configured'], 500);
        }

        $payload = $request->all();
        $expectedSignature = hash('sha512', ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$serverKey);
        if (($payload['signature_key'] ?? '') !== $expectedSignature) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $order = Order::where('order_code', $payload['order_id'] ?? '')->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        PaymentEvent::create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'event_type' => (string) ($payload['transaction_status'] ?? 'unknown'),
            'payload_json' => $payload,
            'created_at' => now(),
        ]);

        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $isPaid = in_array($transactionStatus, ['capture', 'settlement'], true);
        $isFailed = in_array($transactionStatus, ['deny', 'cancel', 'expire'], true);

        DB::transaction(function () use ($order, $payload, $isPaid, $isFailed): void {
            $order->payment_reference = $payload['transaction_id'] ?? null;

            if ($isPaid) {
                $license = $order->license_id ? License::find($order->license_id) : null;
                if (!$license) {
                    $license = License::create([
                        'license_key' => $this->generateLicenseKey(),
                        'plan' => $order->plan,
                        'status' => 'active',
                        'expires_at' => now()->addDays(30),
                        'max_accounts' => 1,
                    ]);
                }

                $order->license_id = $license->id;
                $order->status = 'paid';
                $order->paid_at = now();
            } elseif ($isFailed) {
                $order->status = 'failed';
            } else {
                $order->status = 'pending';
            }

            $order->save();
        });

        return response()->json(['ok' => true]);
    }

    private function generateLicenseKey(): string
    {
        return 'TFB-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
    }
}
