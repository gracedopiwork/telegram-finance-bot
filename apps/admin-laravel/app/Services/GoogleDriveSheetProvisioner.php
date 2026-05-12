<?php

namespace App\Services;

use App\Models\Order;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveSheetProvisioner
{
    public function isConfigured(): bool
    {
        $path = $this->credentialsPath();
        $template = (string) config('services.google.user_sheet_template_id', '');

        return $template !== '' && $path !== '' && is_readable($path);
    }

    /**
     * @return array{id: string, url: string}
     */
    public function copyTemplateForOrder(Order $order): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Google Sheet template atau service account belum dikonfigurasi.');
        }

        $templateId = (string) config('services.google.user_sheet_template_id');
        $title = sprintf('Finance — %s (%s)', $order->full_name, $order->order_code);

        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->timeout(60)
            ->post(
                "https://www.googleapis.com/drive/v3/files/{$templateId}/copy?supportsAllDrives=true&fields=id,name,webViewLink",
                ['name' => $title]
            );

        if (! $response->successful()) {
            Log::warning('Drive files.copy gagal', ['body' => $response->body(), 'status' => $response->status()]);
            throw new \RuntimeException('Drive API copy gagal: HTTP '.$response->status());
        }

        $id = (string) $response->json('id', '');
        if ($id === '') {
            throw new \RuntimeException('Drive API copy: id kosong.');
        }

        $url = (string) ($response->json('webViewLink') ?: "https://docs.google.com/spreadsheets/d/{$id}/edit");

        return ['id' => $id, 'url' => $url];
    }

    private function accessToken(): string
    {
        $path = $this->credentialsPath();
        $scopes = ['https://www.googleapis.com/auth/drive'];
        $creds = new ServiceAccountCredentials($scopes, $path);
        $token = $creds->fetchAuthToken();
        if (empty($token['access_token'])) {
            throw new \RuntimeException('Gagal ambil access token Google: '.json_encode($token));
        }

        return (string) $token['access_token'];
    }

    private function credentialsPath(): string
    {
        return (string) config('services.google.service_account_json', '');
    }
}
