<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetPrivacyService
{
    public function configureSpreadsheetForOrder(string $spreadsheetId, Order $order): void
    {
        $spreadsheetId = trim($spreadsheetId);
        if ($spreadsheetId === '') {
            return;
        }

        $saEmail = $this->serviceAccountEmail();
        if ($saEmail === '') {
            Log::warning('GoogleSheetPrivacy: client_email tidak ditemukan di service account JSON.');

            return;
        }

        $this->ensureTabTitles($spreadsheetId);
        $this->clearSheetProtections($spreadsheetId);
        $this->applySheetProtections($spreadsheetId, $saEmail);

        $customerEmail = filter_var(trim((string) $order->email), FILTER_VALIDATE_EMAIL);
        if (! $customerEmail) {
            Log::info('GoogleSheetPrivacy: email pelanggan tidak valid, lewati share/transfer.', ['order' => $order->order_code]);

            return;
        }

        $this->grantReader($spreadsheetId, $customerEmail);

        if (config('services.google.transfer_sheet_ownership', true)) {
            $this->transferOwnership($spreadsheetId, $customerEmail, $saEmail);
        }
    }

    private function ensureTabTitles(string $spreadsheetId): void
    {
        $transactionTab = (string) config('services.google.sheet_transaction_tab', 'Transaksi');
        $dashboardTab = (string) config('services.google.sheet_dashboard_tab', 'Dashboard');

        $meta = $this->sheetsGet($spreadsheetId, 'sheets.properties(sheetId,title,index)');
        $sheets = $meta['sheets'] ?? [];
        if ($sheets === []) {
            return;
        }

        $requests = [];
        $titles = array_map(fn ($s) => (string) ($s['properties']['title'] ?? ''), $sheets);

        $hasTransaction = in_array($transactionTab, $titles, true);
        $hasDashboard = in_array($dashboardTab, $titles, true);

        if (! $hasTransaction) {
            $firstId = $sheets[0]['properties']['sheetId'] ?? null;
            if ($firstId !== null && ($titles[0] ?? '') !== $dashboardTab) {
                $requests[] = [
                    'updateSheetProperties' => [
                        'properties' => ['sheetId' => $firstId, 'title' => $transactionTab],
                        'fields' => 'title',
                    ],
                ];
            }
        }

        if (! $hasDashboard) {
            $requests[] = [
                'addSheet' => [
                    'properties' => [
                        'title' => $dashboardTab,
                        'index' => 1,
                    ],
                ],
            ];
        }

        if ($requests !== []) {
            $this->sheetsBatchUpdate($spreadsheetId, $requests);
        }
    }

    private function clearSheetProtections(string $spreadsheetId): void
    {
        $meta = $this->sheetsGet($spreadsheetId, 'sheets(protectedRanges(protectedRangeId))');
        $requests = [];

        foreach ($meta['sheets'] ?? [] as $sheet) {
            foreach ($sheet['protectedRanges'] ?? [] as $range) {
                $id = $range['protectedRangeId'] ?? null;
                if ($id !== null) {
                    $requests[] = ['deleteProtectedRange' => ['protectedRangeId' => $id]];
                }
            }
        }

        if ($requests !== []) {
            $this->sheetsBatchUpdate($spreadsheetId, $requests);
        }
    }

    private function applySheetProtections(string $spreadsheetId, string $saEmail): void
    {
        $transactionTab = (string) config('services.google.sheet_transaction_tab', 'Transaksi');
        $dashboardTab = (string) config('services.google.sheet_dashboard_tab', 'Dashboard');

        $meta = $this->sheetsGet($spreadsheetId, 'sheets.properties(sheetId,title)');
        $requests = [];

        foreach ($meta['sheets'] ?? [] as $sheet) {
            $title = (string) ($sheet['properties']['title'] ?? '');
            if (! in_array($title, [$transactionTab, $dashboardTab], true)) {
                continue;
            }

            $sheetId = $sheet['properties']['sheetId'] ?? null;
            if ($sheetId === null) {
                continue;
            }

            $requests[] = [
                'addProtectedRange' => [
                    'protectedRange' => [
                        'range' => ['sheetId' => $sheetId],
                        'description' => $title === $dashboardTab
                            ? 'Dashboard — hanya service account (sync admin)'
                            : 'Transaksi — hanya service account (bot)',
                        'warningOnly' => false,
                        'editors' => [
                            'users' => [$saEmail],
                        ],
                    ],
                ],
            ];
        }

        if ($requests === []) {
            Log::warning('GoogleSheetPrivacy: tab Transaksi/Dashboard tidak ditemukan.', ['spreadsheet_id' => $spreadsheetId]);

            return;
        }

        $this->sheetsBatchUpdate($spreadsheetId, $requests);
    }

    private function grantReader(string $spreadsheetId, string $email): void
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->post(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}/permissions?supportsAllDrives=true&sendNotificationEmail=false",
                [
                    'type' => 'user',
                    'role' => 'reader',
                    'emailAddress' => $email,
                ]
            );

        if (! $response->successful() && $response->status() !== 409) {
            Log::warning('GoogleSheetPrivacy: grant reader gagal', [
                'email' => $email,
                'detail' => $this->formatApiError($response->body(), $response->status()),
            ]);
        }
    }

    private function transferOwnership(string $spreadsheetId, string $customerEmail, string $saEmail): void
    {
        if (! $this->serviceAccountOwnsFile($spreadsheetId, $saEmail)) {
            Log::info('GoogleSheetPrivacy: lewati transfer ownership (file bukan milik service account). Gunakan Shared drive yang hanya berisi SA, atau file akan tetap di folder admin.', [
                'spreadsheet_id' => $spreadsheetId,
            ]);

            return;
        }

        $this->ensureServiceAccountWriter($spreadsheetId, $saEmail);

        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->post(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}/permissions?supportsAllDrives=true&transferOwnership=true&sendNotificationEmail=false",
                [
                    'type' => 'user',
                    'role' => 'owner',
                    'emailAddress' => $customerEmail,
                ]
            );

        if (! $response->successful()) {
            Log::warning('GoogleSheetPrivacy: transfer ownership gagal', [
                'email' => $customerEmail,
                'detail' => $this->formatApiError($response->body(), $response->status()),
            ]);

            return;
        }

        $this->ensureServiceAccountWriter($spreadsheetId, $saEmail);
    }

    private function serviceAccountOwnsFile(string $spreadsheetId, string $saEmail): bool
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->get(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}",
                ['supportsAllDrives' => 'true', 'fields' => 'owners(emailAddress)']
            );

        if (! $response->successful()) {
            return false;
        }

        foreach ($response->json('owners', []) as $owner) {
            if (strcasecmp((string) ($owner['emailAddress'] ?? ''), $saEmail) === 0) {
                return true;
            }
        }

        return false;
    }

    private function ensureServiceAccountWriter(string $spreadsheetId, string $saEmail): void
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->post(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}/permissions?supportsAllDrives=true&sendNotificationEmail=false",
                [
                    'type' => 'user',
                    'role' => 'writer',
                    'emailAddress' => $saEmail,
                ]
            );

        if (! $response->successful() && $response->status() !== 409) {
            Log::warning('GoogleSheetPrivacy: grant writer SA gagal', [
                'email' => $saEmail,
                'detail' => $this->formatApiError($response->body(), $response->status()),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sheetsGet(string $spreadsheetId, string $fields): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}", ['fields' => $fields]);

        if (! $response->successful()) {
            throw new \RuntimeException('Sheets API get gagal: '.$this->formatApiError($response->body(), $response->status()));
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $requests
     */
    private function sheetsBatchUpdate(string $spreadsheetId, array $requests): void
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(60)
            ->post(
                "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate",
                ['requests' => $requests]
            );

        if (! $response->successful()) {
            throw new \RuntimeException('Sheets API batchUpdate gagal: '.$this->formatApiError($response->body(), $response->status()));
        }
    }

    private function serviceAccountEmail(): string
    {
        $path = (string) config('services.google.service_account_json', '');
        if ($path === '' || ! is_readable($path)) {
            return '';
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? (string) ($data['client_email'] ?? '') : '';
    }

    private function accessToken(): string
    {
        return GoogleServiceAccountToken::get();
    }

    private function formatApiError(string $body, int $status): string
    {
        $json = json_decode($body, true);
        if (is_array($json) && isset($json['error']['message'])) {
            return "HTTP {$status}: ".$json['error']['message'];
        }

        return 'HTTP '.$status;
    }
}
