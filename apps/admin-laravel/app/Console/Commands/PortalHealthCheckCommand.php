<?php

namespace App\Console\Commands;

use App\Support\FinancialBaselineSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PortalHealthCheckCommand extends Command
{
    protected $signature = 'portal:health-check';

    protected $description = 'Cek kesiapan database portal (baseline, transaksi bot)';

    public function handle(): int
    {
        $ok = true;

        foreach (['financial_baselines', 'bot_transactions', 'licenses', 'orders'] as $table) {
            $exists = Schema::hasTable($table);
            $this->line(($exists ? '✓' : '✗')." {$table}");
            if (! $exists) {
                $ok = false;
            }
        }

        if (FinancialBaselineSchema::isReady()) {
            $this->info('✓ financial_baselines schema lengkap');
        } else {
            $this->error('✗ financial_baselines belum lengkap — jalankan: php artisan migrate --force');
            $ok = false;
        }

        if (config('baseline_assessment.financial_stage.profile') === null) {
            $this->error('✗ config baseline_assessment kosong — jalankan: php artisan config:clear');
            $ok = false;
        } else {
            $this->info('✓ config baseline_assessment terbaca');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
