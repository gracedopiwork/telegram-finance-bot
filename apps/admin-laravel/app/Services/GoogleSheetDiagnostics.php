<?php

namespace App\Services;

class GoogleSheetDiagnostics
{
    /**
     * @return list<array{check: string, status: string, detail: string}>
     */
    public function run(GoogleDriveSheetProvisioner $provisioner): array
    {
        $rows = [];

        $jsonPath = (string) config('services.google.service_account_json', '');
        $templateId = (string) config('services.google.user_sheet_template_id', '');
        $parentId = trim((string) config('services.google.copy_parent_folder_id', ''));
        $impersonate = trim((string) config('services.google.drive_impersonate_user', ''));
        $usesOAuth = GoogleServiceAccountToken::useOAuthRefreshToken();

        $rows[] = $this->row(
            'GOOGLE_SERVICE_ACCOUNT_JSON',
            $jsonPath !== '' ? 'ok' : 'fail',
            $jsonPath !== '' ? $jsonPath : 'Kosong — isi path absolut ke file JSON service account'
        );

        if ($jsonPath !== '') {
            $rows[] = $this->row(
                'File JSON terbaca',
                is_readable($jsonPath) ? 'ok' : 'fail',
                is_readable($jsonPath) ? 'OK' : "Tidak terbaca: {$jsonPath}"
            );
        }

        $saEmail = $this->serviceAccountEmail($jsonPath);
        if ($saEmail) {
            $rows[] = $this->row('Service account email', 'info', $saEmail);
        }

        $rows[] = $this->row(
            'GOOGLE_USER_SHEET_TEMPLATE_ID',
            $templateId !== '' ? 'ok' : 'fail',
            $templateId !== '' ? $templateId : 'Kosong — ID dari URL Google Sheet di Drive (bukan file .xlsx lokal)'
        );

        $rows[] = $this->row(
            'GOOGLE_DRIVE_COPY_PARENT_ID',
            $parentId !== '' ? 'ok' : 'warn',
            $parentId !== '' ? $parentId : 'Kosong — salinan ke My Drive SA, sering gagal storageQuotaExceeded'
        );

        $rows[] = $this->row(
            'Auth copy (OAuth)',
            $usesOAuth ? 'ok' : 'info',
            $usesOAuth ? 'Pakai GOOGLE_OAUTH_* (disarankan tanpa Shared Drive)' : 'Tidak di-set'
        );

        if (! $usesOAuth && $impersonate !== '') {
            $rows[] = $this->row(
                'GOOGLE_DRIVE_IMPERSONATE_USER',
                'warn',
                "{$impersonate} — butuh Domain-wide delegation di Google Admin, atau pakai OAuth"
            );
        }

        $rows[] = $this->row(
            'isConfigured()',
            $provisioner->isConfigured() ? 'ok' : 'fail',
            $provisioner->isConfigured()
                ? 'Template + JSON siap'
                : 'Salah satu: template ID kosong atau JSON tidak terbaca'
        );

        if (! $provisioner->isConfigured()) {
            return $rows;
        }

        try {
            GoogleServiceAccountToken::get();
            $rows[] = $this->row('Access token Google', 'ok', 'Berhasil diambil');
        } catch (\Throwable $e) {
            $rows[] = $this->row('Access token Google', 'fail', $e->getMessage());

            return $rows;
        }

        try {
            $template = $provisioner->inspectDriveFile($templateId);
            if ($template['ok']) {
                $rows[] = $this->row(
                    'Akses template Drive',
                    'ok',
                    ($template['name'] ?? 'template').' — share Editor ke '.$saEmail
                );
            } else {
                $rows[] = $this->row(
                    'Akses template Drive',
                    'fail',
                    ($template['error'] ?? 'tidak bisa akses').' — bagikan template ke service account (Editor)'
                );
            }
        } catch (\Throwable $e) {
            $rows[] = $this->row('Akses template Drive', 'fail', $e->getMessage());
        }

        if ($parentId !== '') {
            try {
                $folder = $provisioner->inspectDriveFile($parentId);
                if ($folder['ok']) {
                    $inShared = ! empty($folder['in_shared_drive']);
                    $rows[] = $this->row(
                        'Folder salinan',
                        $inShared ? 'ok' : 'warn',
                        ($folder['name'] ?? $parentId).($inShared ? ' (Shared drive)' : ' (bukan Shared drive — risiko quota)')
                    );
                } else {
                    $rows[] = $this->row('Folder salinan', 'fail', $folder['error'] ?? 'tidak bisa akses');
                }
            } catch (\Throwable $e) {
                $rows[] = $this->row('Folder salinan', 'fail', $e->getMessage());
            }
        }

        return $rows;
    }

    public function likelyCause(): string
    {
        if (! (string) config('services.google.user_sheet_template_id', '')) {
            return 'GOOGLE_USER_SHEET_TEMPLATE_ID belum di-set. Upload master_finance_workbook.xlsx ke Google Drive, salin ID dari URL, bagikan ke service account.';
        }

        $path = (string) config('services.google.service_account_json', '');
        if ($path === '' || ! is_readable($path)) {
            return 'GOOGLE_SERVICE_ACCOUNT_JSON salah path atau file tidak terbaca oleh user web server (www-data).';
        }

        if (! GoogleServiceAccountToken::useOAuthRefreshToken()
            && trim((string) config('services.google.copy_parent_folder_id', '')) === ''
            && trim((string) config('services.google.drive_impersonate_user', '')) !== '') {
            return 'Impersonate aktif tanpa OAuth/Shared Drive — token atau copy sering gagal. Isi GOOGLE_OAUTH_* atau GOOGLE_DRIVE_COPY_PARENT_ID (Shared drive).';
        }

        if (! GoogleServiceAccountToken::useOAuthRefreshToken()
            && trim((string) config('services.google.copy_parent_folder_id', '')) === '') {
            return 'storageQuotaExceeded — service account tidak punya kuota Drive. Buat Shared Drive + GOOGLE_DRIVE_COPY_PARENT_ID, atau isi GOOGLE_OAUTH_*.';
        }

        return 'Jalankan: php artisan google:sheet-setup --provision=KODE_ORDER dan cek storage/logs/laravel.log';
    }

    /**
     * @return array{check: string, status: string, detail: string}
     */
    private function row(string $check, string $status, string $detail): array
    {
        return compact('check', 'status', 'detail');
    }

    private function serviceAccountEmail(string $jsonPath): ?string
    {
        if ($jsonPath === '' || ! is_readable($jsonPath)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($jsonPath), true);

        return is_array($data) ? ($data['client_email'] ?? null) : null;
    }
}
