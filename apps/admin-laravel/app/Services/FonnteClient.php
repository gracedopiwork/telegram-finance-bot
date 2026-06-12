<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteClient
{
    /**
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function sendText(string $target, string $message): array
    {
        $token = trim((string) config('services.fonnte.token', ''));
        if ($token === '') {
            throw new \RuntimeException('FONNTE_TOKEN belum di-set di .env');
        }

        $url = (string) config('services.fonnte.api_url', 'https://api.fonnte.com/send');

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])
            ->asForm()
            ->timeout((int) config('services.fonnte.timeout', 30))
            ->post($url, [
                'target' => $target,
                'message' => $message,
                'countryCode' => '0',
            ]);

        $body = $response->json();
        if (! is_array($body)) {
            Log::warning('Fonnte response bukan JSON', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Fonnte tidak merespons JSON (HTTP '.$response->status().').');
        }

        $ok = filter_var($body['status'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (! $ok) {
            $reason = (string) ($body['reason'] ?? $body['detail'] ?? 'status false');
            throw new \RuntimeException('Fonnte menolak pengiriman: '.$reason);
        }

        return $body;
    }
}
