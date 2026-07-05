<?php

namespace App\Services;

use App\Models\BotAiDailyStat;
use App\Models\BotTransaction;
use App\Models\FinancialBaseline;
use App\Models\License;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServerHealthService
{
    /**
     * Snapshot kesehatan server + usage aplikasi.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $resources = $this->resourceMetrics();
        $usage = $this->usageMetrics();
        $tier = $this->recommendedTier($usage['active_users_30d']);
        $alerts = $this->buildAlerts($resources, $usage, $tier);

        return [
            'checked_at' => now()->toDateTimeString(),
            'hostname' => gethostname() ?: 'unknown',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'resources' => $resources,
            'usage' => $usage,
            'tier' => $tier,
            'alerts' => $alerts,
            'status' => $this->overallStatus($alerts),
        ];
    }

    /**
     * Proyeksi biaya bulanan (server + API).
     *
     * @return array<string, mixed>
     */
    public function costProjection(): array
    {
        $usage = $this->usageMetrics();
        $tier = $this->recommendedTier($usage['active_users_30d']);
        $aiProvider = (string) config('server_capacity.default_ai_provider', 'claude_haiku');
        $costPerParse = (int) (config("server_capacity.ai_cost_per_parse_idr.{$aiProvider}") ?? 54);
        $aiMonthly = $usage['ai_parses_30d'] * $costPerParse;
        $serverMonthly = (int) ($tier['monthly_idr'] ?? 0);
        $totalMonthly = $serverMonthly + $aiMonthly;

        $tiers = collect(config('server_capacity.tiers', []))
            ->map(function (array $t) use ($usage, $costPerParse) {
                $parses = max(1, (int) $usage['active_users_30d']) * 30;
                $aiEst = $parses * $costPerParse;

                return array_merge($t, [
                    'ai_estimate_idr' => $aiEst,
                    'total_estimate_idr' => (int) $t['monthly_idr'] + $aiEst,
                ]);
            })
            ->values()
            ->all();

        return [
            'active_users_30d' => $usage['active_users_30d'],
            'ai_parses_30d' => $usage['ai_parses_30d'],
            'ai_provider' => $aiProvider,
            'cost_per_parse_idr' => $costPerParse,
            'server_monthly_idr' => $serverMonthly,
            'ai_monthly_idr' => $aiMonthly,
            'total_monthly_idr' => $totalMonthly,
            'recommended_tier' => $tier,
            'tiers' => $tiers,
            'upgrade_next_tier' => $this->nextTier($tier['key'] ?? 'pilot'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceMetrics(): array
    {
        $diskPath = storage_path();
        $diskFree = @disk_free_space($diskPath);
        $diskTotal = @disk_total_space($diskPath);
        $diskUsedPercent = null;
        if (is_numeric($diskFree) && is_numeric($diskTotal) && $diskTotal > 0) {
            $diskUsedPercent = round((1 - ($diskFree / $diskTotal)) * 100, 1);
        }

        $memory = $this->linuxMemory();
        $cpuCount = $this->cpuCount();
        $load = sys_getloadavg();
        $load1 = is_array($load) ? round((float) ($load[0] ?? 0), 2) : null;

        $loadRatio = ($load1 !== null && $cpuCount > 0) ? round($load1 / $cpuCount, 2) : null;

        return [
            'cpu_count' => $cpuCount,
            'load_1m' => $load1,
            'load_ratio' => $loadRatio,
            'memory' => $memory,
            'disk_path' => $diskPath,
            'disk_free_bytes' => is_numeric($diskFree) ? (int) $diskFree : null,
            'disk_total_bytes' => is_numeric($diskTotal) ? (int) $diskTotal : null,
            'disk_used_percent' => $diskUsedPercent,
            'php_memory_limit' => ini_get('memory_limit') ?: null,
            'php_memory_peak_bytes' => memory_get_peak_usage(true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function usageMetrics(): array
    {
        $since30 = Carbon::today()->subDays(29)->startOfDay();

        $activeUsers30d = 0;
        if (Schema::hasTable('bot_transactions')) {
            $activeUsers30d = (int) BotTransaction::query()
                ->where('recorded_at', '>=', $since30)
                ->distinct()
                ->count('telegram_user_id');
        }

        $licensesActivated = Schema::hasTable('licenses')
            ? (int) License::query()->whereNotNull('assigned_user_id')->where('assigned_user_id', '>', 0)->count()
            : 0;

        $paidOrders = Schema::hasTable('orders')
            ? (int) Order::query()->where('status', 'paid')->count()
            : 0;

        $transactions30d = Schema::hasTable('bot_transactions')
            ? (int) BotTransaction::query()->where('recorded_at', '>=', $since30)->count()
            : 0;

        $baselines = Schema::hasTable('financial_baselines')
            ? (int) FinancialBaseline::query()->count()
            : 0;

        $aiParses30d = 0;
        if (Schema::hasTable('bot_ai_daily_stats')) {
            $aiParses30d = (int) BotAiDailyStat::query()
                ->where('stat_date', '>=', $since30->toDateString())
                ->get()
                ->sum(fn (BotAiDailyStat $r) => (int) $r->success_count
                    + (int) $r->fallback_count
                    + (int) $r->rate_limit_count
                    + (int) $r->error_count);
        }

        $dbConnections = null;
        try {
            $row = DB::selectOne('SHOW STATUS LIKE "Threads_connected"');
            $dbConnections = isset($row->Value) ? (int) $row->Value : null;
        } catch (\Throwable) {
            $dbConnections = null;
        }

        return [
            'active_users_30d' => $activeUsers30d,
            'licenses_activated' => $licensesActivated,
            'paid_orders' => $paidOrders,
            'transactions_30d' => $transactions30d,
            'baselines' => $baselines,
            'ai_parses_30d' => $aiParses30d,
            'db_connections' => $dbConnections,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recommendedTier(int $activeUsers): array
    {
        $tiers = config('server_capacity.tiers', []);
        foreach ($tiers as $tier) {
            if ($activeUsers <= (int) ($tier['max_active_users'] ?? 0)) {
                return $tier;
            }
        }

        return is_array($tiers) ? (end($tiers) ?: []) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function nextTier(string $currentKey): ?array
    {
        $tiers = config('server_capacity.tiers', []);
        $found = false;
        foreach ($tiers as $tier) {
            if ($found) {
                return $tier;
            }
            if (($tier['key'] ?? '') === $currentKey) {
                $found = true;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resources
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>  $tier
     * @return list<array{level: string, message: string}>
     */
    private function buildAlerts(array $resources, array $usage, array $tier): array
    {
        $alerts = [];
        $th = config('server_capacity.thresholds', []);

        $ramPercent = $resources['memory']['used_percent'] ?? null;
        if ($ramPercent !== null) {
            if ($ramPercent >= (int) ($th['ram_critical_percent'] ?? 90)) {
                $alerts[] = ['level' => 'critical', 'message' => "RAM terpakai {$ramPercent}% — pertimbangkan upgrade VPS segera."];
            } elseif ($ramPercent >= (int) ($th['ram_warning_percent'] ?? 75)) {
                $alerts[] = ['level' => 'warning', 'message' => "RAM terpakai {$ramPercent}% — mulai pantau beban server."];
            }
        }

        $diskPercent = $resources['disk_used_percent'] ?? null;
        if ($diskPercent !== null) {
            if ($diskPercent >= (int) ($th['disk_critical_percent'] ?? 92)) {
                $alerts[] = ['level' => 'critical', 'message' => "Disk terpakai {$diskPercent}% — kosongkan log/backup atau perbesar storage."];
            } elseif ($diskPercent >= (int) ($th['disk_warning_percent'] ?? 80)) {
                $alerts[] = ['level' => 'warning', 'message' => "Disk terpakai {$diskPercent}%."];
            }
        }

        $loadRatio = $resources['load_ratio'] ?? null;
        if ($loadRatio !== null) {
            if ($loadRatio >= (float) ($th['load_critical_multiplier'] ?? 2.0)) {
                $alerts[] = ['level' => 'critical', 'message' => "Load CPU tinggi (ratio {$loadRatio}) — server kewalahan."];
            } elseif ($loadRatio >= (float) ($th['load_warning_multiplier'] ?? 1.2)) {
                $alerts[] = ['level' => 'warning', 'message' => "Load CPU meningkat (ratio {$loadRatio})."];
            }
        }

        $maxUsers = (int) ($tier['max_active_users'] ?? 0);
        $active = (int) ($usage['active_users_30d'] ?? 0);
        if ($maxUsers > 0) {
            $pct = round(($active / $maxUsers) * 100, 1);
            if ($pct >= (int) ($th['user_capacity_warning_percent'] ?? 80)) {
                $next = $this->nextTier((string) ($tier['key'] ?? ''));
                $nextLabel = $next['label'] ?? 'tier lebih besar';
                $alerts[] = [
                    'level' => 'warning',
                    'message' => "Kapasitas user tier {$tier['label']} terpakai {$pct}% ({$active}/{$maxUsers}). Pertimbangkan upgrade ke {$nextLabel}.",
                ];
            }
        }

        if ($alerts === []) {
            $alerts[] = ['level' => 'ok', 'message' => 'Tidak ada sinyal kritis. Server masih dalam batas aman untuk tier saat ini.'];
        }

        return $alerts;
    }

    /**
     * @param  list<array{level: string, message: string}>  $alerts
     */
    private function overallStatus(array $alerts): string
    {
        foreach ($alerts as $alert) {
            if (($alert['level'] ?? '') === 'critical') {
                return 'critical';
            }
        }
        foreach ($alerts as $alert) {
            if (($alert['level'] ?? '') === 'warning') {
                return 'warning';
            }
        }

        return 'ok';
    }

    /**
     * @return array{total_bytes: ?int, available_bytes: ?int, used_percent: ?float, source: string}
     */
    private function linuxMemory(): array
    {
        if (! is_readable('/proc/meminfo')) {
            return [
                'total_bytes' => null,
                'available_bytes' => null,
                'used_percent' => null,
                'source' => 'unavailable',
            ];
        }

        $info = file_get_contents('/proc/meminfo');
        if (! is_string($info)) {
            return ['total_bytes' => null, 'available_bytes' => null, 'used_percent' => null, 'source' => 'unavailable'];
        }

        $totalKb = $this->parseMeminfo($info, 'MemTotal');
        $availableKb = $this->parseMeminfo($info, 'MemAvailable');
        if ($availableKb === null) {
            $availableKb = $this->parseMeminfo($info, 'MemFree');
        }

        $total = $totalKb !== null ? $totalKb * 1024 : null;
        $available = $availableKb !== null ? $availableKb * 1024 : null;
        $usedPercent = ($total !== null && $available !== null && $total > 0)
            ? round((1 - ($available / $total)) * 100, 1)
            : null;

        return [
            'total_bytes' => $total,
            'available_bytes' => $available,
            'used_percent' => $usedPercent,
            'source' => 'linux_proc',
        ];
    }

    private function parseMeminfo(string $content, string $key): ?int
    {
        if (preg_match('/^'.$key.':\s+(\d+)/m', $content, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function cpuCount(): int
    {
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if (is_string($cpuinfo)) {
                $count = preg_match_all('/^processor\s*:/m', $cpuinfo);

                return max(1, (int) $count);
            }
        }

        $env = getenv('NUMBER_OF_PROCESSORS');

        return max(1, (int) ($env ?: 1));
    }
}
