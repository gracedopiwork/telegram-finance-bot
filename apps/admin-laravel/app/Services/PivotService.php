<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PivotService
{
    public function isReady(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function clientId(): string
    {
        return trim((string) config('services.pivot.client_id', ''));
    }

    public function clientSecret(): string
    {
        return trim((string) config('services.pivot.client_secret', ''), " \t\n\r\0\x0B\"'");
    }

    public function callbackKey(): string
    {
        return trim((string) config('services.pivot.callback_key', ''), " \t\n\r\0\x0B\"'");
    }

    public function baseUrl(): string
    {
        $configured = rtrim(trim((string) config('services.pivot.base_url', '')), '/');
        if ($configured !== '') {
            return $configured;
        }

        return (bool) config('services.pivot.is_production', false)
            ? 'https://api.pivot-payment.com'
            : 'https://api-stg.pivot-payment.com';
    }

    public function notificationUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/webhooks/pivot';
    }

    /**
     * Create Pivot REDIRECT payment session.
     *
     * @param  array{
     *   order_id: string,
     *   gross_amount: int|float|string,
     *   full_name?: string,
     *   email?: string,
     *   phone?: string,
     *   item_details?: array<int, array{id?: string, name?: string, price?: int|float, quantity?: int}>,
     *   success_return_url?: string,
     *   failure_return_url?: string,
     *   expiration_return_url?: string
     * }  $order
     * @return array{id: ?string, payment_url: ?string, client_reference_id: string, raw: array<string, mixed>}
     */
    public function createRedirectPayment(array $order): array
    {
        if (! $this->isReady()) {
            throw new \RuntimeException('PIVOT_CLIENT_ID / PIVOT_CLIENT_SECRET kosong. Isi di .env lalu php artisan config:clear.');
        }

        $grossAmount = (int) round((float) $order['gross_amount']);
        if ($grossAmount < 1) {
            throw new \RuntimeException('gross_amount tidak valid untuk Pivot.');
        }

        $orderId = (string) $order['order_id'];
        $finishBase = $this->finishUrlForOrder($orderId);
        $successUrl = trim((string) ($order['success_return_url'] ?? '')) ?: $finishBase.'&result=success';
        $failureUrl = trim((string) ($order['failure_return_url'] ?? '')) ?: $finishBase.'&result=failure';
        $expirationUrl = trim((string) ($order['expiration_return_url'] ?? '')) ?: $finishBase.'&result=expired';

        [$givenName, $surname] = $this->splitName((string) ($order['full_name'] ?? 'Customer'));
        $email = trim((string) ($order['email'] ?? ''));
        $phone = $this->normalizePhoneParts((string) ($order['phone'] ?? ''));

        $productDetails = $this->buildProductDetails(
            is_array($order['item_details'] ?? null) ? $order['item_details'] : [],
            $grossAmount,
            $orderId,
        );

        $customerBlock = [
            'givenName' => $givenName,
            'surname' => $surname,
            'email' => $email !== '' ? $email : 'noreply@yourfinancialdoctor.id',
            'phoneNumber' => $phone,
        ];

        $payload = [
            'clientReferenceId' => $orderId,
            'amount' => [
                'value' => $grossAmount,
                'currency' => 'IDR',
            ],
            'paymentType' => 'SINGLE',
            'mode' => 'REDIRECT',
            'autoConfirm' => false,
            'bypassStatusPage' => false,
            'redirectUrl' => [
                'successReturnUrl' => $successUrl,
                'failureReturnUrl' => $failureUrl,
                'expirationReturnUrl' => $expirationUrl,
            ],
            'customer' => $customerBlock,
            'orderInformation' => [
                'productDetails' => $productDetails,
                'billingInfo' => array_merge($customerBlock, [
                    'addressLine1' => 'Digital',
                    'city' => 'Jakarta',
                    'provinceState' => 'DKI Jakarta',
                    'country' => 'ID',
                    'postalCode' => '10110',
                ]),
            ],
            'statementDescriptor' => Str::limit((string) config('services.pivot.statement_descriptor', 'YFD'), 22, ''),
            'metadata' => [
                'orderCode' => $orderId,
            ],
        ];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->post($this->baseUrl().'/v2/payments', $payload);

        if (! $response->successful()) {
            Log::warning('Pivot API menolak create payment', [
                'http_status' => $response->status(),
                'body' => $response->body(),
                'base_url' => $this->baseUrl(),
                'order_id' => $orderId,
            ]);
            throw new \RuntimeException('Gagal membuat transaksi Pivot: '.$response->body());
        }

        $json = $response->json() ?? [];
        $data = is_array($json['data'] ?? null) ? $json['data'] : $json;

        $paymentUrl = $data['paymentUrl'] ?? $data['payment_url'] ?? null;
        $sessionId = $data['id'] ?? null;

        if (! is_string($paymentUrl) || $paymentUrl === '') {
            Log::warning('Pivot create payment tanpa paymentUrl', ['body' => $json]);
            throw new \RuntimeException('Pivot tidak mengembalikan paymentUrl.');
        }

        return [
            'id' => is_string($sessionId) ? $sessionId : null,
            'payment_url' => $paymentUrl,
            'client_reference_id' => $orderId,
            'raw' => is_array($json) ? $json : [],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchPaymentByClientReferenceId(string $clientReferenceId): ?array
    {
        if (! $this->isReady() || $clientReferenceId === '') {
            return null;
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(30)
            ->get($this->baseUrl().'/v2/payments', [
                'clientReferenceId' => $clientReferenceId,
            ]);

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning('Pivot status API gagal', [
                'client_reference_id' => $clientReferenceId,
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $json = $response->json() ?? [];
        $data = $json['data'] ?? null;

        if (is_array($data) && array_is_list($data)) {
            return $data[0] ?? null;
        }

        if (is_array($data)) {
            return $data;
        }

        return null;
    }

    public function verifyCallbackRequest(Request $request): bool
    {
        $expected = $this->callbackKey();
        if ($expected === '') {
            // Belum dikonfigurasi: izinkan agar staging bisa diuji, tapi log peringatan.
            Log::warning('Pivot webhook: PIVOT_CALLBACK_KEY kosong — callback diterima tanpa validasi key');

            return true;
        }

        $candidates = [
            (string) $request->header('X-Callback-Api-Key', ''),
            (string) $request->header('X-Callback-Token', ''),
            (string) $request->header('X-Api-Key', ''),
            (string) $request->header('X-API-KEY', ''),
            (string) $request->input('apiKey', ''),
            (string) $request->input('callbackApiKey', ''),
        ];

        $auth = (string) $request->header('Authorization', '');
        if (str_starts_with(strtolower($auth), 'bearer ')) {
            $candidates[] = trim(substr($auth, 7));
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }

    public function accessToken(): string
    {
        $cacheKey = 'pivot.access_token.'.md5($this->baseUrl().'|'.$this->clientId());

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::withHeaders([
            'X-MERCHANT-ID' => $this->clientId(),
            'X-MERCHANT-SECRET' => $this->clientSecret(),
        ])
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($this->baseUrl().'/v1/access-token', [
                'grantType' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            Log::warning('Pivot access-token gagal', [
                'http_status' => $response->status(),
                'body' => $response->body(),
                'base_url' => $this->baseUrl(),
            ]);
            throw new \RuntimeException('Gagal mengambil Pivot access token: '.$response->body());
        }

        $json = $response->json() ?? [];
        $data = is_array($json['data'] ?? null) ? $json['data'] : $json;
        $token = (string) ($data['accessToken'] ?? $data['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Pivot access token kosong.');
        }

        $expiresIn = (int) ($data['expiresIn'] ?? $data['expires_in'] ?? 900);
        $ttl = max(60, $expiresIn - 60);
        Cache::put($cacheKey, $token, now()->addSeconds($ttl));

        return $token;
    }

    private function finishUrlForOrder(string $orderId): string
    {
        $configured = trim((string) config('services.pivot.finish_url', ''));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL)) {
            $sep = str_contains($configured, '?') ? '&' : '?';

            return $configured.$sep.'order_id='.rawurlencode($orderId);
        }

        return route('checkout.finish', ['order_id' => $orderId]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName) ?: 'Customer';
        $parts = preg_split('/\s+/', $fullName, 2) ?: [$fullName];
        $given = Str::limit((string) ($parts[0] ?? 'Customer'), 50, '');
        $surname = Str::limit(trim((string) ($parts[1] ?? '-')) ?: '-', 50, '');

        return [$given, $surname];
    }

    /**
     * @return array{countryCode: string, number: string}
     */
    private function normalizePhoneParts(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return ['countryCode' => '+62', 'number' => '81234567890'];
        }

        if (str_starts_with($digits, '62')) {
            return ['countryCode' => '+62', 'number' => Str::limit(substr($digits, 2) ?: '81234567890', 15, '')];
        }

        if (str_starts_with($digits, '0')) {
            return ['countryCode' => '+62', 'number' => Str::limit(substr($digits, 1) ?: '81234567890', 15, '')];
        }

        return ['countryCode' => '+62', 'number' => Str::limit($digits, 15, '')];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function buildProductDetails(array $items, int $grossAmount, string $orderId): array
    {
        if ($items === []) {
            return [[
                'type' => 'DIGITAL',
                'category' => 'SOFTWARE',
                'subCategory' => 'SUBSCRIPTION',
                'name' => 'YFD Order '.$orderId,
                'description' => 'Digital product',
                'quantity' => 1,
                'price' => ['value' => $grossAmount, 'currency' => 'IDR'],
            ]];
        }

        $out = [];
        foreach ($items as $row) {
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $price = (int) round((float) ($row['price'] ?? 0));
            $out[] = [
                'type' => 'DIGITAL',
                'category' => 'SOFTWARE',
                'subCategory' => 'SUBSCRIPTION',
                'name' => Str::limit((string) ($row['name'] ?? 'Item'), 100, ''),
                'description' => (string) ($row['id'] ?? 'item'),
                'quantity' => $qty,
                'price' => ['value' => $price, 'currency' => 'IDR'],
            ];
        }

        return $out;
    }
}
