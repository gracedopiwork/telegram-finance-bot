<?php

namespace App\Console\Commands;

use App\Services\ClaudeJsonService;
use App\Services\MidtransService;
use App\Services\PortalCheckoutService;
use App\Support\FinancialBaselineSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PortalHealthCheckCommand extends Command
{
    protected $signature = 'portal:health-check {email? : Opsional — cek kelayakan upgrade bot untuk email ini}';

    protected $description = 'Cek kesiapan database portal, integrasi Midtrans, dan Claude AI';

    public function handle(
        MidtransService $midtrans,
        ClaudeJsonService $claude,
        PortalCheckoutService $checkout,
    ): int {
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

        if ($midtrans->isSnapReady()) {
            $env = config('services.midtrans.is_production') ? 'production' : 'sandbox';
            $this->info("✓ Midtrans Snap siap ({$env})");
        } else {
            $this->error('✗ Midtrans belum siap — isi MIDTRANS_CLIENT_KEY dan MIDTRANS_SERVER_KEY di .env lalu php artisan config:clear');
            $ok = false;
        }

        if ($claude->isConfigured()) {
            $this->info('✓ Claude AI dikonfigurasi (ANTHROPIC_API_KEY terbaca)');
        } else {
            $this->error('✗ Claude belum dikonfigurasi — isi ANTHROPIC_API_KEY di .env lalu php artisan config:clear && php artisan cache:clear');
            $ok = false;
        }

        $email = strtolower(trim((string) $this->argument('email')));
        if ($email !== '') {
            $status = $checkout->botUpgradeEligibility($email);
            $this->line('');
            $this->line("Upgrade bot untuk {$email}:");
            $this->line('  eligible: '.($status['eligible'] ? 'yes' : 'no'));
            $this->line('  midtrans_ready: '.($status['midtrans_ready'] ? 'yes' : 'no'));
            $this->line('  can_pay: '.($status['can_pay'] ? 'yes' : 'no'));
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
