<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MidtransService
{
    public function createSnapTransaction(array $order): array
    {
        $isProduction = (bool) config('services.midtrans.is_production', false);
        $base = $isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $apiBase = $isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $serverKey = (string) config('services.midtrans.server_key');

        $payload = [
            'transaction_details' => [
                'order_id'     => $order['order_id'],
                'gross_amount' => $order['gross_amount'],
            ],
            'customer_details' => [
                'first_name' => $order['full_name'],
                'email'      => $order['email'],
                'phone'      => $order['phone'] ?? null,
            ],
        ];

        if (! empty($order['item_details']) && is_array($order['item_details'])) {
            $payload['item_details'] = $order['item_details'];
        }

        // Callback finish (redirect setelah bayar). URL ini opsional dan diambil dari config.
        if ($finishUrl = config('services.midtrans.finish_url')) {
            $payload['callbacks'] = ['finish' => $finishUrl];
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->post($apiBase.'/v1/transactions', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Gagal membuat transaksi Midtrans: '.$response->body());
        }

        $data = $response->json();
        return [
            'token' => $data['token'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? ($base.'/snap/v2/vtweb/'.$data['token']),
        ];
    }
}
