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

        $customerEmail = $this->customerEmailFromOrder($order);
        if ($customerEmail === null) {
            Log::info('GoogleSheetPrivacy: email checkout tidak valid, lewati share/transfer.', ['order' => $order->order_code]);

            return;
        }

        $this->ensureServiceAccountWriter($spreadsheetId, $saEmail);
        $this->shareSpreadsheetWithCustomerEmail($spreadsheetId, $customerEmail, $saEmail, $order->order_code);

        if (config('services.google.sheet_anyone_with_link_reader', false)) {
            $this->grantAnyoneWithLinkReader($spreadsheetId);
        }

        $this->applyAccessFallbackIfNeeded($spreadsheetId, $customerEmail, $order->order_code);

        $this->ensureServiceAccountWriter($spreadsheetId, $saEmail);
    }

    /**
     * @return array{ok: bool, email: ?string, owners: list<string>, permissions: list<string>, message: string}
     */
    public function diagnoseAccess(string $spreadsheetId, Order $order): array
    {
        $spreadsheetId = trim($spreadsheetId);
        $customerEmail = $this->customerEmailFromOrder($order);
        $permissions = $this->listPermissionEmails($spreadsheetId);
        $owners = $this->fileOwnerEmails($spreadsheetId);
        $emailOk = $customerEmail !== null && $this->customerHasDriveAccess($spreadsheetId, $customerEmail);
        $linkOk = $this->hasAnyoneWithLinkAccess($spreadsheetId);
        $hasAccess = $emailOk || $linkOk;

        $message = $emailOk
            ? "Akses OK untuk {$customerEmail}"
            : ($linkOk
                ? 'Email checkout belum terdaftar, tetapi "siapa pun dengan link" aktif — buka dengan akun Google mana pun.'
                : 'Belum ada izin. Jalankan --reshare dan cek GOOGLE_OAUTH_* di .env.');

        return [
            'ok' => $hasAccess,
            'email' => $customerEmail,
            'owners' => $owners,
            'permissions' => $permissions,
            'message' => $message,
        ];
    }

    private function hasAnyoneWithLinkAccess(string $spreadsheetId): bool
    {
        foreach ($this->listPermissionEmails($spreadsheetId) as $entry) {
            if (str_starts_with($entry, 'anyone:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bagikan ulang sheet ke email yang diinput saat checkout (idempotent).
     */
    public function shareSpreadsheetWithOrderEmail(Order $order): ?string
    {
        $spreadsheetId = trim((string) $order->spreadsheet_id);
        $customerEmail = $this->customerEmailFromOrder($order);
        if ($spreadsheetId === '' || $customerEmail === null) {
            return null;
        }

        $saEmail = $this->serviceAccountEmail();
        if ($saEmail !== '') {
            $this->ensureServiceAccountWriter($spreadsheetId, $saEmail);
            $this->shareSpreadsheetWithCustomerEmail($spreadsheetId, $customerEmail, $saEmail, $order->order_code);
            $this->ensureServiceAccountWriter($spreadsheetId, $saEmail);
        }

        return $customerEmail;
    }

    private function customerEmailFromOrder(Order $order): ?string
    {
        $email = strtolower(trim((string) $order->email));
        if ($email === '') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: null;
    }

    private function shareSpreadsheetWithCustomerEmail(
        string $spreadsheetId,
        string $customerEmail,
        string $saEmail,
        ?string $orderCode = null
    ): void {
        // Writer dulu (akses langsung); reader saja sering tidak cukup untuk buka spreadsheet.
        $this->grantWriter($spreadsheetId, $customerEmail);

        if (! $this->customerHasDriveAccess($spreadsheetId, $customerEmail)) {
            $this->grantReader($spreadsheetId, $customerEmail);
        }

        if (! $this->customerHasDriveAccess($spreadsheetId, $customerEmail)
            && $this->shouldTransferOwnership($customerEmail)) {
            $this->transferOwnership($spreadsheetId, $customerEmail, $saEmail);
        }

        $this->applyAccessFallbackIfNeeded($spreadsheetId, $customerEmail, $orderCode);

        if ($this->customerHasDriveAccess($spreadsheetId, $customerEmail) || $this->customerOwnsFile($spreadsheetId, $customerEmail)) {
            Log::info('GoogleSheetPrivacy: akses email checkout OK', [
                'order' => $orderCode,
                'email' => $customerEmail,
                'spreadsheet_id' => $spreadsheetId,
            ]);

            return;
        }

        Log::error('GoogleSheetPrivacy: pelanggan belum punya akses Drive setelah share', [
            'order' => $orderCode,
            'email' => $customerEmail,
            'spreadsheet_id' => $spreadsheetId,
            'owners' => $this->fileOwnerEmails($spreadsheetId),
            'permissions' => $this->listPermissionEmails($spreadsheetId),
        ]);
    }

    private function shouldTransferOwnership(string $customerEmail): bool
    {
        if (! config('services.google.transfer_sheet_ownership', false)) {
            return false;
        }

        $ownerEmail = $this->credentialUserEmail();
        if ($ownerEmail === '') {
            return false;
        }

        $ownerDomain = strtolower((string) substr(strrchr($ownerEmail, '@'), 1));
        $customerDomain = strtolower((string) substr(strrchr($customerEmail, '@'), 1));

        return $ownerDomain !== '' && $ownerDomain === $customerDomain;
    }

    private function applyAccessFallbackIfNeeded(string $spreadsheetId, string $customerEmail, ?string $orderCode): void
    {
        if ($this->customerHasDriveAccess($spreadsheetId, $customerEmail)) {
            return;
        }

        if (! config('services.google.sheet_fallback_link_reader', true)
            && ! config('services.google.sheet_anyone_with_link_reader', false)) {
            return;
        }

        $this->grantAnyoneWithLinkReader($spreadsheetId);

        Log::warning('GoogleSheetPrivacy: pakai fallback "siapa pun dengan link" (share email checkout gagal)', [
            'order' => $orderCode,
            'email' => $customerEmail,
            'spreadsheet_id' => $spreadsheetId,
        ]);
    }

    /**
     * @return list<string>
     */
    private function listPermissionEmails(string $spreadsheetId): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->get(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}/permissions",
                [
                    'supportsAllDrives' => 'true',
                    'fields' => 'permissions(emailAddress,role,type,deleted)',
                    'pageSize' => 100,
                ]
            );

        if (! $response->successful()) {
            return [];
        }

        $out = [];
        foreach ($response->json('permissions', []) as $permission) {
            if (! empty($permission['deleted'])) {
                continue;
            }
            $email = strtolower(trim((string) ($permission['emailAddress'] ?? '')));
            $role = (string) ($permission['role'] ?? '');
            $type = (string) ($permission['type'] ?? '');
            if ($type === 'anyone') {
                $out[] = "anyone:{$role}";
            } elseif ($email !== '') {
                $out[] = "{$email}:{$role}";
            }
        }

        return $out;
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
        $this->grantDriveRole($spreadsheetId, $email, 'reader');
    }

    private function grantWriter(string $spreadsheetId, string $email): void
    {
        $this->grantDriveRole($spreadsheetId, $email, 'writer');
    }

    private function grantDriveRole(string $spreadsheetId, string $email, string $role): void
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->post(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}/permissions?supportsAllDrives=true&sendNotificationEmail=false",
                [
                    'type' => 'user',
                    'role' => $role,
                    'emailAddress' => $email,
                ]
            );

        if ($response->successful() || $response->status() === 409) {
            return;
        }

        Log::warning("GoogleSheetPrivacy: grant {$role} ke email checkout gagal", [
            'email' => $email,
            'detail' => $this->formatApiError($response->body(), $response->status()),
        ]);
    }

    private function grantAnyoneWithLinkReader(string $spreadsheetId): void
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->post(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}/permissions?supportsAllDrives=true&sendNotificationEmail=false",
                [
                    'type' => 'anyone',
                    'role' => 'reader',
                ]
            );

        if (! $response->successful() && $response->status() !== 409) {
            Log::warning('GoogleSheetPrivacy: grant anyone-with-link gagal', [
                'detail' => $this->formatApiError($response->body(), $response->status()),
            ]);
        }
    }

    private function transferOwnership(string $spreadsheetId, string $customerEmail, string $saEmail): void
    {
        if ($this->customerOwnsFile($spreadsheetId, $customerEmail)) {
            return;
        }

        if (! $this->serviceAccountOwnsFile($spreadsheetId, $saEmail)
            && ! $this->fileOwnedByCredentialUser($spreadsheetId)) {
            Log::warning('GoogleSheetPrivacy: lewati transfer — file bukan milik service account maupun akun OAuth/impersonate.', [
                'spreadsheet_id' => $spreadsheetId,
                'email' => $customerEmail,
                'owners' => $this->fileOwnerEmails($spreadsheetId),
            ]);

            return;
        }

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

        Log::info('GoogleSheetPrivacy: ownership ditransfer ke email checkout', [
            'email' => $customerEmail,
            'spreadsheet_id' => $spreadsheetId,
        ]);
    }

    private function customerHasDriveAccess(string $spreadsheetId, string $customerEmail): bool
    {
        if ($this->customerOwnsFile($spreadsheetId, $customerEmail)) {
            return true;
        }

        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->get(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}/permissions",
                [
                    'supportsAllDrives' => 'true',
                    'fields' => 'permissions(emailAddress,role,type,deleted)',
                ]
            );

        if (! $response->successful()) {
            return false;
        }

        foreach ($response->json('permissions', []) as $permission) {
            if (! empty($permission['deleted'])) {
                continue;
            }
            if (strcasecmp((string) ($permission['emailAddress'] ?? ''), $customerEmail) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function fileOwnerEmails(string $spreadsheetId): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(30)
            ->get(
                "https://www.googleapis.com/drive/v3/files/{$spreadsheetId}",
                ['supportsAllDrives' => 'true', 'fields' => 'owners(emailAddress)']
            );

        if (! $response->successful()) {
            return [];
        }

        $emails = [];
        foreach ($response->json('owners', []) as $owner) {
            $email = strtolower(trim((string) ($owner['emailAddress'] ?? '')));
            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    private function fileOwnedByCredentialUser(string $spreadsheetId): bool
    {
        $credentialEmail = $this->credentialUserEmail();
        if ($credentialEmail === '') {
            return false;
        }

        foreach ($this->fileOwnerEmails($spreadsheetId) as $ownerEmail) {
            if (strcasecmp($ownerEmail, $credentialEmail) === 0) {
                return true;
            }
        }

        return false;
    }

    private function credentialUserEmail(): string
    {
        if (GoogleServiceAccountToken::useOAuthRefreshToken()) {
            return $this->oauthUserEmail();
        }

        return strtolower(trim((string) config('services.google.drive_impersonate_user', '')));
    }

    private function oauthUserEmail(): string
    {
        static $cached = null;
        if (is_string($cached)) {
            return $cached;
        }

        $response = Http::withToken($this->accessToken())
            ->timeout(20)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if ($response->successful()) {
            $cached = strtolower(trim((string) $response->json('email', '')));

            return $cached;
        }

        $cached = '';

        return '';
    }

    private function customerOwnsFile(string $spreadsheetId, string $customerEmail): bool
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
            if (strcasecmp((string) ($owner['emailAddress'] ?? ''), $customerEmail) === 0) {
                return true;
            }
        }

        return false;
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
