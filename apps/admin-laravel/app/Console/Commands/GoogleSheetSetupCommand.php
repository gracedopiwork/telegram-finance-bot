<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\UserSheet;
use App\Services\GoogleDriveSheetProvisioner;
use App\Services\GoogleSheetPrivacyService;
use App\Services\GoogleServiceAccountToken;
use Illuminate\Console\Command;

class GoogleSheetSetupCommand extends Command
{
    protected $signature = 'google:sheet-setup
                            {--provision= : Order code untuk salin ulang template (hanya jika spreadsheet_id masih kosong)}
                            {--reshare= : Order code — perbarui izin Drive ke email checkout (sheet sudah ada)}';

    protected $description = 'Cek konfigurasi Google Drive (service account, template, folder) dan opsional salin ulang sheet untuk satu order';

    public function handle(GoogleDriveSheetProvisioner $provisioner): int
    {
        $jsonPath = (string) config('services.google.service_account_json', '');
        $templateId = (string) config('services.google.user_sheet_template_id', '');
        $parentId = trim((string) config('services.google.copy_parent_folder_id', ''));

        $this->info('Konfigurasi Google Sheet (order delivery)');
        $this->table(
            ['Variabel', 'Nilai'],
            [
                ['GOOGLE_SERVICE_ACCOUNT_JSON', $jsonPath !== '' ? $jsonPath : '(kosong)'],
                ['GOOGLE_USER_SHEET_TEMPLATE_ID', $templateId !== '' ? $templateId : '(kosong)'],
                ['GOOGLE_DRIVE_COPY_PARENT_ID', $parentId !== '' ? $parentId : '(kosong — salinan ke My Drive service account)'],
            ]
        );

        if ($jsonPath === '' || $templateId === '') {
            $this->error('Set GOOGLE_SERVICE_ACCOUNT_JSON dan GOOGLE_USER_SHEET_TEMPLATE_ID di .env lalu: php artisan config:clear');

            return self::FAILURE;
        }

        if (! is_readable($jsonPath)) {
            $this->error("File service account tidak terbaca: {$jsonPath}");
            $this->line('Gunakan path absolut di VPS, contoh: /var/www/telegram-finance-bot/apps/admin-laravel/storage/app/google-service-account.json');

            return self::FAILURE;
        }

        $email = $this->serviceAccountEmail($jsonPath);
        if ($email) {
            $this->line("Service account: <comment>{$email}</comment>");
            $this->line('Bagikan file template (dan folder salinan jika dipakai) ke email ini dengan peran Editor.');
        }

        if (GoogleServiceAccountToken::useOAuthRefreshToken()) {
            $this->line('Auth Drive: <info>OAuth refresh token (akun Anda)</info> — tidak perlu Domain-wide delegation.');
        } else {
            $impersonate = trim((string) config('services.google.drive_impersonate_user', ''));
            if ($impersonate !== '') {
                $this->line("Impersonate (salin pakai kuota user): <comment>{$impersonate}</comment>");
                $this->line('Wajib: domain-wide delegation di Admin, ATAU isi GOOGLE_OAUTH_* (OAuth Playground).');
            }
        }

        if (! $provisioner->isConfigured()) {
            $this->error('isConfigured() false — cek path & template ID.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Akses Drive API…');

        try {
            $template = $provisioner->inspectDriveFile($templateId);
            if ($template['ok']) {
                $this->line("Template OK: <info>{$template['name']}</info> ({$templateId})");
            } else {
                $this->error("Template GAGAL: {$template['error']}");
                $this->line('Pastikan GOOGLE_USER_SHEET_TEMPLATE_ID = ID dari URL spreadsheet (bukan nama file).');

                return self::FAILURE;
            }

            if ($parentId !== '') {
                $folder = $provisioner->inspectDriveFile($parentId);
                if ($folder['ok']) {
                    $this->line("Folder salinan OK: <info>{$folder['name']}</info> ({$parentId})");
                    if (empty($folder['in_shared_drive'])) {
                        $this->newLine();
                        $this->warn('Folder ini di My Drive (bukan Shared drive). Salinan sering gagal storageQuotaExceeded.');
                        $this->warn('Buat Shared drive di drive.google.com → tambah laravel-drive@... sebagai Content manager → pakai folder di dalamnya.');
                    } else {
                        $this->line('<info>Folder di Shared drive — cocok untuk salinan.</info>');
                    }
                } else {
                    $this->error("Folder salinan GAGAL: {$folder['error']}");
                    $this->line('Untuk Shared drive: service account harus anggota Shared drive + folder dibagikan Editor.');

                    return self::FAILURE;
                }
            }
        } catch (\Throwable $e) {
            $this->error('Token / Drive API: '.$e->getMessage());

            return self::FAILURE;
        }

        $reshareCode = $this->option('reshare');
        if ($reshareCode) {
            return $this->reshareOrder((string) $reshareCode);
        }

        $orderCode = $this->option('provision');
        if ($orderCode) {
            return $this->provisionOrder($provisioner, (string) $orderCode);
        }

        if ($parentId === '') {
            $this->newLine();
            $this->warn('GOOGLE_DRIVE_COPY_PARENT_ID kosong — salinan masuk My Drive service account (kuota ~0).');
            $this->warn('Jika copy gagal (storageQuotaExceeded), buat folder di Shared Drive, share ke service account, isi ID folder di .env.');
        }

        $this->newLine();
        $this->comment('Setup terlihat OK (akses baca template). Uji salin nyata:');
        $this->comment('  php artisan google:sheet-setup --provision=KODE_ORDER');
        $this->comment('Order yang sudah gagal di checkout tidak otomatis diperbaiki — wajib perintah di atas atau tombol di admin.');
        $this->comment('Worker antrian: php artisan queue:work --queue=default');

        return self::SUCCESS;
    }

    private function provisionOrder(GoogleDriveSheetProvisioner $provisioner, string $orderCode): int
    {
        $order = Order::where('order_code', $orderCode)->first();
        if (! $order) {
            $this->error("Order tidak ditemukan: {$orderCode}");

            return self::FAILURE;
        }

        if ($order->status !== 'paid') {
            $this->error('Order belum status paid.');

            return self::FAILURE;
        }

        if ($order->spreadsheet_id) {
            $this->warn("Order sudah punya spreadsheet_id: {$order->spreadsheet_id}");
            $this->line('Perbarui izin ke email checkout: php artisan google:sheet-setup --reshare='.$orderCode);

            return self::SUCCESS;
        }

        try {
            $result = $provisioner->copyTemplateForOrder($order);
            $order->spreadsheet_id = $result['id'];
            $order->spreadsheet_url = $result['url'];
            $order->save();
            $this->info('Berhasil: '.$result['url']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Salin gagal: '.$e->getMessage());
            $this->line('Detail ada di storage/logs/laravel.log (cari "Drive files.copy" atau "Gagal duplikasi").');

            return self::FAILURE;
        }
    }

    private function reshareOrder(string $orderCode): int
    {
        $order = Order::where('order_code', $orderCode)->first();
        if (! $order) {
            $this->error("Order tidak ditemukan: {$orderCode}");

            return self::FAILURE;
        }

        if ($order->status !== 'paid' || ! $order->spreadsheet_id) {
            $this->error('Order harus lunas dan sudah punya spreadsheet_id.');

            return self::FAILURE;
        }

        $this->info("Email checkout: {$order->email}");
        $this->line("Spreadsheet: {$order->spreadsheet_id}");

        $privacy = app(GoogleSheetPrivacyService::class);

        try {
            $diag = $privacy->ensureOrderAccessible($order);
        } catch (\Throwable $e) {
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        UserSheet::syncFromOrder($order);
        $this->table(['Item', 'Nilai'], [
            ['Email checkout', $diag['email'] ?? '-'],
            ['Pemilik file', implode(', ', $diag['owners']) ?: '-'],
            ['Izin saat ini', implode("\n", $diag['permissions']) ?: '-'],
            ['Status', $diag['message']],
        ]);

        if (! $diag['ok']) {
            $this->warn('Email checkout belum terdaftar di izin. Cek GOOGLE_OAUTH_* dan storage/logs/laravel.log');
            $this->warn('Fallback "siapa pun dengan link" seharusnya aktif (GOOGLE_SHEET_FALLBACK_LINK_READER=true).');
        } else {
            $this->info('Buka link dengan akun Google: '.$order->email);
        }

        if ($order->spreadsheet_url) {
            $this->line($order->spreadsheet_url);
        }

        return $diag['ok'] ? self::SUCCESS : self::FAILURE;
    }

    private function serviceAccountEmail(string $jsonPath): ?string
    {
        $data = json_decode((string) file_get_contents($jsonPath), true);

        return is_array($data) ? ($data['client_email'] ?? null) : null;
    }
}
