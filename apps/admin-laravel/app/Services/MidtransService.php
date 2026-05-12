<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransService
{
    public function createSnapTransaction(array $order): array
    {
        $isProduction = (bool) config('services.midtrans.is_production', false);
        // Snap charge: host "app", path "/snap/v1/transactions" (bukan api.*.midtrans.com/v1/transactions — itu 404).
        $base = $isProduction ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $chargeUrl = $base.'/snap/v1/transactions';
        $serverKey = $this->normalizeServerKey((string) config('services.midtrans.server_key'));

        if ($serverKey === '') {
            throw new \RuntimeException('MIDTRANS_SERVER_KEY kosong. Isi di .env lalu php artisan config:clear.');
        }

        $grossAmount = (int) round((float) $order['gross_amount']);
        if ($grossAmount < 1) {
            throw new \RuntimeException('gross_amount tidak valid untuk Midtrans.');
        }

        $fullName = trim((string) ($order['full_name'] ?? 'Customer'));
        $firstName = Str::limit($fullName, 50, '');
        $lastName = trim(Str::substr($fullName, Str::length($firstName)));
        if ($lastName === '') {
            $lastName = '-';
        }

        $payload = [
            'transaction_details' => [
                'order_id'     => (string) $order['order_id'],
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $firstName,
                'last_name'  => Str::limit($lastName, 50, ''),
                'email'      => trim((string) ($order['email'] ?? '')),
                'phone'      => $this->normalizePhoneForMidtrans((string) ($order['phone'] ?? '')),
            ],
        ];

        if (! empty($order['item_details']) && is_array($order['item_details'])) {
            $normalizedItems = $this->normalizeItemDetails($order['item_details'], $grossAmount);
            if ($normalizedItems !== []) {
                $payload['item_details'] = $normalizedItems;
            }
        }

        // Callback finish (redirect setelah bayar). Bisa dimatikan lewat env jika Midtrans menolak URL (uji isolasi).
        $finishUrl = trim((string) config('services.midtrans.finish_url', ''));
        $useCallbacks = filter_var(config('services.midtrans.use_finish_callback', true), FILTER_VALIDATE_BOOL);
        if ($useCallbacks && $finishUrl !== '' && filter_var($finishUrl, FILTER_VALIDATE_URL)) {
            $payload['callbacks'] = ['finish' => $finishUrl];
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->post($chargeUrl, $payload);

        if (! $response->successful()) {
            Log::warning('Midtrans API menolak charge Snap', [
                'http_status' => $response->status(),
                'body'        => $response->body(),
                'mode'        => $isProduction ? 'production' : 'sandbox',
                'charge_url'  => $chargeUrl,
            ]);
            throw new \RuntimeException('Gagal membuat transaksi Midtrans: '.$response->body());
        }

        $data = $response->json();

        return [
            'token' => $data['token'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? ($base.'/snap/v2/vtweb/'.$data['token']),
        ];
    }

    private function normalizeServerKey(string $key): string
    {
        $key = trim($key);
        if (str_starts_with($key, 'Basic ')) {
            $decoded = base64_decode(substr($key, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                $key = trim(explode(':', $decoded, 2)[0]);
            }
        }

        return trim($key, " \t\n\r\0\x0B\"'");
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItemDetails(array $items, int $grossAmount): array
    {
        $out = [];
        $sum = 0;
        foreach ($items as $row) {
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $price = (int) round((float) ($row['price'] ?? 0));
            $line = $qty * $price;
            $sum += $line;
            $out[] = [
                'id'       => (string) ($row['id'] ?? 'item'),
                'price'    => $price,
                'quantity' => $qty,
                'name'     => Str::limit((string) ($row['name'] ?? 'Item'), 50, ''),
            ];
        }

        if ($sum !== $grossAmount) {
            Log::warning('Midtrans item_details tidak sama dengan gross_amount; item_details dihapus agar charge jalan.', [
                'gross_amount' => $grossAmount,
                'items_sum'    => $sum,
            ]);

            return [];
        }

        return $out;
    }

    private function normalizePhoneForMidtrans(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '081234567890';
        }

        return Str::limit($digits, 20, '');
    }
}
