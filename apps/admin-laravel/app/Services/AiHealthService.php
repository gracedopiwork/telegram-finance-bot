<?php

namespace App\Services;

use App\Models\BotAiDailyStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiHealthService
{
    /** @var array<string, string> */
    private const EVENT_COLUMNS = [
        'success' => 'success_count',
        'rate_limit' => 'rate_limit_count',
        'fallback' => 'fallback_count',
        'error' => 'error_count',
    ];

    public function record(string $event, ?string $detail = null): void
    {
        if (! isset(self::EVENT_COLUMNS[$event])) {
            return;
        }

        $today = Carbon::today()->toDateString();
        $column = self::EVENT_COLUMNS[$event];

        DB::transaction(function () use ($today, $column, $event, $detail): void {
            $row = BotAiDailyStat::query()->lockForUpdate()->firstOrCreate(
                ['stat_date' => $today],
                [
                    'success_count' => 0,
                    'rate_limit_count' => 0,
                    'fallback_count' => 0,
                    'error_count' => 0,
                ],
            );

            $row->increment($column);

            if ($event === 'rate_limit') {
                $row->last_rate_limit_at = now();
            }

            if ($detail !== null && $detail !== '') {
                $row->last_detail = mb_substr($detail, 0, 500);
            }

            $row->save();
        });
    }

    /**
     * @return array{
     *   status: string,
     *   label: string,
     *   message: string,
     *   should_upgrade: bool,
     *   totals: array{success: int, rate_limit: int, fallback: int, error: int, total: int},
     *   fallback_rate: float,
     *   last_rate_limit_at: ?string,
     *   last_detail: ?string
     * }
     */
    public function summary(int $days = 7): array
    {
        $from = Carbon::today()->subDays($days - 1);
        $rows = BotAiDailyStat::query()
            ->where('stat_date', '>=', $from->toDateString())
            ->orderByDesc('stat_date')
            ->get();

        $success = (int) $rows->sum('success_count');
        $rateLimit = (int) $rows->sum('rate_limit_count');
        $fallback = (int) $rows->sum('fallback_count');
        $error = (int) $rows->sum('error_count');
        $total = $success + $rateLimit + $fallback + $error;
        $fallbackRate = $total > 0 ? round((($fallback + $rateLimit) / $total) * 100, 1) : 0.0;

        $lastRateLimit = $rows->firstWhere(fn ($r) => $r->last_rate_limit_at !== null);
        $lastDetail = $rows->firstWhere(fn ($r) => filled($r->last_detail));

        $shouldUpgrade = $rateLimit >= 5
            || ($total >= 20 && $fallbackRate >= 25)
            || $rateLimit >= 3 && $rows->where('stat_date', '>=', Carbon::today()->subDay())->sum('rate_limit_count') >= 3;

        if ($rateLimit >= 10 || ($total >= 30 && $fallbackRate >= 40)) {
            $status = 'critical';
            $label = 'Perlu naikkan limit API';
            $message = 'Claude API sering kena rate limit. Cek kuota/billing di Anthropic Console.';
        } elseif ($shouldUpgrade) {
            $status = 'warning';
            $label = 'Hampir perlu upgrade';
            $message = 'Mulai muncul error rate limit atau parser fallback. Pertimbangkan naikkan limit API Claude.';
        } elseif ($total === 0) {
            $status = 'unknown';
            $label = 'Belum ada data';
            $message = $this->noDataMessage();
        } else {
            $status = 'ok';
            $label = 'API masih cukup';
            $message = 'Tidak ada sinyal kuat untuk menaikkan limit API saat ini.';
        }

        return [
            'status' => $status,
            'label' => $label,
            'message' => $message,
            'should_upgrade' => $shouldUpgrade,
            'totals' => [
                'success' => $success,
                'rate_limit' => $rateLimit,
                'fallback' => $fallback,
                'error' => $error,
                'total' => $total,
            ],
            'fallback_rate' => $fallbackRate,
            'last_rate_limit_at' => $lastRateLimit?->last_rate_limit_at?->toDateTimeString(),
            'last_detail' => $lastDetail?->last_detail,
            'diagnostics' => $this->diagnostics(),
        ];
    }

    /**
     * @return array{laravel_token_set: bool, stats_table_ready: bool}
     */
    public function diagnostics(): array
    {
        return [
            'laravel_token_set' => (string) config('services.bot.internal_api_token', '') !== '',
            'stats_table_ready' => Schema::hasTable('bot_ai_daily_stats'),
        ];
    }

    private function noDataMessage(): string
    {
        $diag = $this->diagnostics();
        $hints = [];

        if (! $diag['stats_table_ready']) {
            $hints[] = 'Jalankan `php artisan migrate --force` di Laravel.';
        }
        if (! $diag['laravel_token_set']) {
            $hints[] = 'Set `BOT_INTERNAL_API_TOKEN` di `apps/admin-laravel/.env`.';
        }
        $hints[] = 'Di `apps/bot-python/.env` wajib ada `LARAVEL_APP_URL` + `BOT_INTERNAL_API_TOKEN` (sama dengan Laravel).';
        $hints[] = 'Lalu `sudo systemctl restart yfd-bot` dan catat 1 transaksi di Telegram.';

        return 'Belum ada laporan dari bot. '.implode(' ', $hints);
    }
}
