<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class DashboardSyncRunner
{
    /**
     * @return array{ok: bool, exit_code: int, output: string}
     */
    public function run(string $version, bool $dryRun = false): array
    {
        $scriptPath = base_path('../bot-python/sync_dashboard.py');
        if (! file_exists($scriptPath)) {
            return [
                'ok' => false,
                'exit_code' => 1,
                'output' => 'sync_dashboard.py tidak ditemukan di apps/bot-python.',
            ];
        }

        $python = (string) config('services.sync_dashboard.python_binary', 'python');
        $command = array_filter([
            $python,
            'sync_dashboard.py',
            '--version',
            $version,
            $dryRun ? '--dry-run' : null,
        ]);

        $result = Process::path(base_path('../bot-python'))
            ->timeout((int) config('services.sync_dashboard.timeout_seconds', 3600))
            ->run($command);

        $out = trim($result->output().$result->errorOutput());

        return [
            'ok' => $result->successful(),
            'exit_code' => $result->exitCode(),
            'output' => Str::limit($out !== '' ? $out : '(tanpa output)', 8000),
        ];
    }
}
