<?php

namespace App\Jobs;

use App\Services\DashboardSyncRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunDashboardSyncJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        public string $version,
        public bool $dryRun = false,
    ) {}

    public function handle(DashboardSyncRunner $runner): void
    {
        $result = $runner->run($this->version, $this->dryRun);

        if ($result['ok']) {
            Log::info('Dashboard sync selesai', [
                'version' => $this->version,
                'dry_run' => $this->dryRun,
            ]);

            return;
        }

        Log::error('Dashboard sync gagal', [
            'version' => $this->version,
            'dry_run' => $this->dryRun,
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
        ]);
    }
}
