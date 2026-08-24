<?php

namespace App\Console\Commands;

use App\Services\ClaudeJsonService;
use App\Services\PivotService;
use App\Services\PortalCheckoutService;
use Illuminate\Console\Command;

class PortalIntegrationsTestCommand extends Command
{
    protected $signature = 'portal:test-integrations {email? : Opsional — cek kelayakan upgrade bot}';

    protected $description = 'Diagnosa Claude AI dan Pivot — baca config runtime dan tes koneksi';

    public function handle(
        ClaudeJsonService $claude,
        PivotService $pivot,
        PortalCheckoutService $checkout,
    ): int {
        $ok = true;

        $this->line('=== Config runtime (bukan isi .env mentah) ===');

        $apiKey = trim((string) config('portal_ai.api_key', ''));
        $aiEnabled = (bool) config('portal_ai.enabled', true);
        $clientId = $pivot->clientId();
        $clientSecret = $pivot->clientSecret();
        $isProduction = (bool) config('services.pivot.is_production', false);

        $this->line('PORTAL_AI_ENABLED: '.($aiEnabled ? 'true' : 'false'));
        $this->line('ANTHROPIC_API_KEY terbaca: '.($apiKey !== '' ? 'yes ('.$this->mask($apiKey).')' : 'KOSONG'));
        $this->line('PIVOT_CLIENT_ID terbaca: '.($clientId !== '' ? 'yes ('.$this->mask($clientId).')' : 'KOSONG'));
        $this->line('PIVOT_CLIENT_SECRET terbaca: '.($clientSecret !== '' ? 'yes ('.$this->mask($clientSecret).')' : 'KOSONG'));
        $this->line('PIVOT_IS_PRODUCTION: '.($isProduction ? 'true' : 'false'));
        $this->line('Pivot base URL: '.$pivot->baseUrl());
        $this->line('Pivot siap: '.($pivot->isReady() ? 'yes' : 'no'));
        $this->line('Claude dikonfigurasi: '.($claude->isConfigured() ? 'yes' : 'no'));

        try {
            $bot = $checkout->botProduct();
            $this->line('Produk bot ter-resolve: '.$bot->code.' ('.$bot->name.')');
        } catch (\Throwable $e) {
            $this->error('✗ Produk bot tidak ditemukan — cek PORTAL_BOT_ONLY_PRODUCT_CODES (harus cocok kode di DB, biasanya yfd-bot-telegram)');
            $ok = false;
        }

        $botCodes = array_values(array_filter(array_map(
            fn (string $v) => trim($v),
            (array) config('portal.bot_only_product_codes', ['yfd-bot-telegram'])
        )));
        if ($botCodes !== [] && ! in_array('yfd-bot-telegram', $botCodes, true)) {
            $this->warn('⚠ PORTAL_BOT_ONLY_PRODUCT_CODES='.implode(',', $botCodes).' — "yfd-first-aid" adalah nama produk, bukan kode DB. Aman jika ter-resolve ke yfd-bot-telegram di atas.');
        }

        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->warn('⚠ bootstrap/cache/config.php ADA — nilai .env baru tidak terbaca sampai: php artisan config:clear');
        }

        $this->line('');
        $this->line('=== Tes Claude API ===');

        if (! $claude->isConfigured()) {
            $this->error('Lewati tes API — ANTHROPIC_API_KEY tidak terbaca oleh Laravel.');
            $this->line('Pastikan key ada di apps/admin-laravel/.env lalu jalankan: php artisan config:clear');
            $ok = false;
        } else {
            $probe = $claude->probe();
            if ($probe['ok'] ?? false) {
                $model = $probe['model'] ?? '?';
                $this->info("✓ Claude API merespons (model: {$model})");
            } else {
                $this->error('✗ Claude API gagal');
                if (isset($probe['model'])) {
                    $this->line('  model: '.$probe['model']);
                }
                if (isset($probe['status'])) {
                    $this->line('  HTTP: '.$probe['status']);
                }
                $this->line('  detail: '.($probe['error'] ?? 'tidak diketahui'));
                if (($probe['status'] ?? 0) === 401) {
                    $this->line('  → API key tidak valid atau kedaluwarsa');
                } elseif (($probe['status'] ?? 0) === 404) {
                    $this->line('  → Model tidak ditemukan — cek PORTAL_AI_MODELS di .env');
                }
                $this->line('  log: storage/logs/laravel.log (cari "Claude API error")');
                $ok = false;
            }
        }

        $email = strtolower(trim((string) $this->argument('email')));
        if ($email !== '') {
            $this->line('');
            $this->line("=== Upgrade bot untuk {$email} ===");
            try {
                $status = $checkout->botUpgradeEligibility($email);
                foreach ($status as $key => $value) {
                    $this->line("  {$key}: ".($value ? 'yes' : 'no'));
                }
            } catch (\Throwable $e) {
                $this->error('  Gagal cek eligibility: '.$e->getMessage());
                $ok = false;
            }
        }

        if (! $pivot->isReady()) {
            $ok = false;
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function mask(string $value): string
    {
        $len = strlen($value);
        if ($len <= 8) {
            return '****';
        }

        return substr($value, 0, 4).'…'.substr($value, -4);
    }
}
