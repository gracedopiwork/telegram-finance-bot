<?php

namespace App\Console\Commands;

use App\Services\ServerHealthService;
use Illuminate\Console\Command;

class PortalServerHealthCommand extends Command
{
    protected $signature = 'portal:server-health';

    protected $description = 'Ringkasan kesehatan server VPS, kapasitas user, dan proyeksi biaya';

    public function handle(ServerHealthService $health): int
    {
        $snapshot = $health->snapshot();
        $costs = $health->costProjection();

        $this->info('=== Server Health ===');
        $this->line('Status: '.strtoupper((string) ($snapshot['status'] ?? 'unknown')));
        $this->line('Host: '.($snapshot['hostname'] ?? '—'));

        $resources = $snapshot['resources'] ?? [];
        $memory = $resources['memory'] ?? [];
        $this->line(sprintf(
            'CPU: %d cores · Load 1m: %s · RAM: %s · Disk: %s',
            (int) ($resources['cpu_count'] ?? 0),
            $resources['load_1m'] ?? '—',
            isset($memory['used_percent']) ? $memory['used_percent'].'%' : '—',
            isset($resources['disk_used_percent']) ? $resources['disk_used_percent'].'%' : '—',
        ));

        $usage = $snapshot['usage'] ?? [];
        $this->line(sprintf(
            'User aktif 30h: %d · Transaksi 30h: %d · AI parse 30h: %d',
            (int) ($usage['active_users_30d'] ?? 0),
            (int) ($usage['transactions_30d'] ?? 0),
            (int) ($usage['ai_parses_30d'] ?? 0),
        ));

        $tier = $snapshot['tier'] ?? [];
        $this->line('Tier rekomendasi: '.($tier['label'] ?? '—').' (max '.number_format((int) ($tier['max_active_users'] ?? 0)).' user)');

        foreach ($snapshot['alerts'] ?? [] as $alert) {
            $prefix = match ($alert['level'] ?? '') {
                'critical' => '✗',
                'warning' => '⚠',
                default => '✓',
            };
            $this->line("  {$prefix} ".$alert['message']);
        }

        $this->newLine();
        $this->info('=== Biaya estimasi / bulan ===');
        $fmt = fn (int $n) => 'Rp '.number_format($n, 0, ',', '.');
        $this->line('VPS: '.$fmt((int) ($costs['server_monthly_idr'] ?? 0)));
        $this->line('AI:  '.$fmt((int) ($costs['ai_monthly_idr'] ?? 0)));
        $this->line('Total: '.$fmt((int) ($costs['total_monthly_idr'] ?? 0)));

        return ($snapshot['status'] ?? '') === 'critical' ? self::FAILURE : self::SUCCESS;
    }
}
