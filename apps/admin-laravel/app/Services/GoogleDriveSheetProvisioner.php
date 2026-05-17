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

        $body = ['name' => $title];
        $parentId = trim((string) config('services.google.copy_parent_folder_id', ''));
        if ($parentId !== '') {
            $body['parents'] = [$parentId];
        }

        $response = Http::withToken($token)
            ->timeout(60)
            ->post(
                "https://www.googleapis.com/drive/v3/files/{$templateId}/copy?supportsAllDrives=true&fields=id,name,webViewLink",
                $body
            );

        if (! $response->successful()) {
            $detail = $this->formatDriveError($response->body(), $response->status());
            Log::warning('Drive files.copy gagal', ['detail' => $detail, 'body' => $response->body(), 'status' => $response->status()]);
            throw new \RuntimeException('Drive API copy gagal: '.$detail);
        }

        $id = (string) $response->json('id', '');
        if ($id === '') {
            throw new \RuntimeException('Drive API copy: id kosong.');
        }

        $url = (string) ($response->json('webViewLink') ?: "https://docs.google.com/spreadsheets/d/{$id}/edit");

        try {
            app(GoogleSheetPrivacyService::class)->configureSpreadsheetForOrder($id, $order);
        } catch (\Throwable $e) {
            Log::error('Google Sheet privacy/permission gagal untuk order '.$order->order_code, [
                'spreadsheet_id' => $id,
                'exception' => $e->getMessage(),
            ]);
        }

        return ['id' => $id, 'url' => $url];
    }

    private function accessToken(): string
    {
        $path = $this->credentialsPath();
        $scopes = [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/spreadsheets',
        ];
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

    /**
     * Cek akses Drive ke file/folder (tanpa menyalin).
     *
     * @return array{ok: bool, status: int, name: ?string, error: ?string}
     */
    public function inspectDriveFile(string $fileId): array
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            return ['ok' => false, 'status' => 0, 'name' => null, 'error' => 'ID kosong'];
        }

        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->get(
                "https://www.googleapis.com/drive/v3/files/{$fileId}",
                ['supportsAllDrives' => 'true', 'fields' => 'id,name,mimeType']
            );

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => $response->status(),
                'name' => $response->json('name'),
                'error' => null,
            ];
        }

        return [
            'ok' => false,
            'status' => $response->status(),
            'name' => null,
            'error' => $this->formatDriveError($response->body(), $response->status()),
        ];
    }

    private function formatDriveError(string $body, int $status): string
    {
        $json = json_decode($body, true);
        if (is_array($json)) {
            $reason = $json['error']['errors'][0]['reason'] ?? null;
            $message = $json['error']['message'] ?? null;
            if ($reason && $message) {
                return "HTTP {$status} ({$reason}): {$message}";
            }
            if ($message) {
                return "HTTP {$status}: {$message}";
            }
        }

        return 'HTTP '.$status.(strlen($body) > 0 ? ' — '.substr($body, 0, 400) : '');
    }
}
