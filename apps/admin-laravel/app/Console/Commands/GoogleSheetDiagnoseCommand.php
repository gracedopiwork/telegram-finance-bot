<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveSheetProvisioner;
use App\Services\GoogleSheetDiagnostics;
use Illuminate\Console\Command;

class GoogleSheetDiagnoseCommand extends Command
{
    protected $signature = 'google:diagnose-sheet';

    protected $description = 'Diagnosa kenapa Google Sheet per order tidak terbentuk (env, token, template, folder)';

    public function handle(GoogleSheetDiagnostics $diagnostics, GoogleDriveSheetProvisioner $provisioner): int
    {
        $this->info('Diagnosa Google Sheet provisioning');
        $this->newLine();

        $rows = $diagnostics->run($provisioner);
        $this->table(
            ['Cek', 'Status', 'Detail'],
            array_map(fn (array $r) => [$r['check'], $this->statusLabel($r['status']), $r['detail']], $rows)
        );

        $hasFail = collect($rows)->contains(fn (array $r) => $r['status'] === 'fail');

        $this->newLine();
        if ($hasFail) {
            $this->error('Kemungkinan penyebab utama:');
            $this->line($diagnostics->likelyCause());
            $this->newLine();
            $this->comment('Uji salin nyata: php artisan google:sheet-setup --provision=KODE_ORDER_LUNAS');
            $this->comment('Pastikan queue worker jalan: php artisan queue:work');

            return self::FAILURE;
        }

        $this->info('Konfigurasi dasar OK. Jika order tetap tanpa sheet, cek queue + log:');
        $this->line('  grep -E "Gagal duplikasi|Drive files.copy|provisioning dilewati" storage/logs/laravel.log | tail -20');

        return self::SUCCESS;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'ok' => 'OK',
            'fail' => 'GAGAL',
            'warn' => 'PERINGATAN',
            default => strtoupper($status),
        };
    }
}
