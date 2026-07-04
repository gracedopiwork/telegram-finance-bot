<?php

namespace App\Console\Commands;

use App\Services\ClaudeJsonService;
use App\Services\MidtransService;
use App\Services\PortalCheckoutService;
use Illuminate\Console\Command;

class PortalIntegrationsTestCommand extends Command
{
    protected $signature = 'portal:test-integrations {email? : Opsional — cek kelayakan upgrade bot}';

    protected $description = 'Diagnosa Claude AI dan Midtrans — baca config runtime dan tes koneksi';

    public function handle(
        ClaudeJsonService $claude,
        MidtransService $midtrans,
        PortalCheckoutService $checkout,
    ): int {
        $ok = true;

        $this->line('=== Config runtime (bukan isi .env mentah) ===');

        $apiKey = trim((string) config('portal_ai.api_key', ''));
        $aiEnabled = (bool) config('portal_ai.enabled', true);
        $clientKey = $midtrans->clientKey();
        $serverKey = trim((string) config('services.midtrans.server_key', ''));
        $isProduction = (bool) config('services.midtrans.is_production', false);

        $this->line('PORTAL_AI_ENABLED: '.($aiEnabled ? 'true' : 'false'));
        $this->line('ANTHROPIC_API_KEY terbaca: '.($apiKey !== '' ? 'yes ('.$this->mask($apiKey).')' : 'KOSONG'));
        $this->line('MIDTRANS_CLIENT_KEY terbaca: '.($clientKey !== '' ? 'yes ('.$this->mask($clientKey).')' : 'KOSONG'));
        $this->line('MIDTRANS_SERVER_KEY terbaca: '.($serverKey !== '' ? 'yes ('.$this->mask($serverKey).')' : 'KOSONG'));
        $this->line('MIDTRANS_IS_PRODUCTION: '.($isProduction ? 'true' : 'false'));
        $this->line('Midtrans Snap siap: '.($midtrans->isSnapReady() ? 'yes' : 'no'));
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
            $parsed = $claude->generate(
                'Balas JSON: {"insights":["tes ok"],"recommendations":["tes ok"]}',
                0.0,
            );

            if ($parsed !== null && is_array($parsed['insights'] ?? null)) {
                $this->info('✓ Claude API merespons dan JSON valid');
            } else {
                $this->error('✗ Claude API gagal — cek storage/logs/laravel.log untuk "Claude API error"');
                $this->line('Penyebab umum: API key salah/expired, model tidak tersedia, atau server tidak bisa outbound ke api.anthropic.com');
                $ok = false;
            }
        }

        $email = strtolower(trim((string) $this->argument('email')));
        if ($email !== '') {
            $this->line('');
            $this->line("=== Upgrade bot untuk {$email} ===");
            $status = $checkout->botUpgradeEligibility($email);
            foreach ($status as $key => $value) {
                $this->line("  {$key}: ".($value ? 'yes' : 'no'));
            }
        }

        if (! $midtrans->isSnapReady()) {
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
