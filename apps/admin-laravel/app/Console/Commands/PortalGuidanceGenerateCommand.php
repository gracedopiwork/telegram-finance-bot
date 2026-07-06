<?php

namespace App\Console\Commands;

use App\Services\PortalGuidanceBatchService;
use Illuminate\Console\Command;

class PortalGuidanceGenerateCommand extends Command
{
    protected $signature = 'portal:generate-guidance
        {type=weekly : weekly|monthly|all}
        {--period= : Y-m untuk monthly atau anchor week (opsional)}
        {--force : Generate ulang meski snapshot sudah ada}';

    protected $description = 'Generate clinical summary mingguan, doctor\'s note & behavioral guidance bulanan';

    public function handle(PortalGuidanceBatchService $batch): int
    {
        $type = strtolower((string) $this->argument('type'));
        $force = (bool) $this->option('force');

        if (! in_array($type, ['weekly', 'monthly', 'all'], true)) {
            $this->error('Type harus weekly, monthly, atau all.');

            return self::FAILURE;
        }

        if ($type === 'weekly' || $type === 'all') {
            $this->info('Generating weekly clinical summaries…');
            $weekly = $batch->generateWeeklyClinicalSummaries(null, $force);
            $this->table(
                ['Metric', 'Count'],
                collect($weekly)->map(fn ($v, $k) => [$k, $v])->values()->all(),
            );
        }

        if ($type === 'monthly' || $type === 'all') {
            $period = $this->option('period');
            $monthKey = is_string($period) && preg_match('/^\d{4}-\d{2}$/', $period) ? $period : null;
            $this->info('Generating monthly doctor\'s notes…');
            $monthly = $batch->generateMonthlyDoctorsNotes($monthKey, $force);
            $this->table(
                ['Metric', 'Count'],
                collect($monthly)->map(fn ($v, $k) => [$k, $v])->values()->all(),
            );

            $this->info('Generating monthly behavioral guidance…');
            $behavioral = $batch->generateMonthlyBehavioralGuidance($monthKey, $force);
            $this->table(
                ['Metric', 'Count'],
                collect($behavioral)->map(fn ($v, $k) => [$k, $v])->values()->all(),
            );
        }

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
