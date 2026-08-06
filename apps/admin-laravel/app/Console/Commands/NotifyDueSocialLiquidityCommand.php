<?php

namespace App\Console\Commands;

use App\Models\BotSocialPayable;
use App\Models\BotSocialReceivable;
use App\Services\SocialLiquidityService;
use App\Services\TelegramBotClient;
use Illuminate\Console\Command;

class NotifyDueSocialLiquidityCommand extends Command
{
    protected $signature = 'social-liquidity:notify-due {--dry-run : Jangan kirim / update, hanya hitung}';

    protected $description = 'Kirim notifikasi Telegram untuk piutang/utang sosial yang jatuh tempo';

    public function handle(SocialLiquidityService $social, TelegramBotClient $telegram): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! $telegram->configured()) {
            $this->error('TELEGRAM_BOT_TOKEN belum di-set di Laravel .env');

            return self::FAILURE;
        }

        $receivables = $social->dueReceivablesForNotify();
        $payables = $social->duePayablesForNotify();
        $sent = 0;
        $skipped = 0;

        /** @var array<int, list<string>> $byUser */
        $byUser = [];

        foreach ($receivables as $row) {
            $uid = (int) $row->telegram_user_id;
            $byUser[$uid][] = $this->receivableLine($row);
        }
        foreach ($payables as $row) {
            $uid = (int) $row->telegram_user_id;
            $byUser[$uid][] = $this->payableLine($row);
        }

        foreach ($byUser as $uid => $lines) {
            $text = "Pengingat Likuiditas Sosial\n\n".implode("\n", $lines)
                ."\n\nCek tracker di dashboard portal untuk tindak lanjut.";

            if ($dry) {
                $this->line("[dry-run] user {$uid}: ".count($lines).' item');
                $sent++;

                continue;
            }

            if ($telegram->sendMessage($uid, $text)) {
                $sent++;
                $this->markNotified($uid, $receivables, $payables);
            } else {
                $skipped++;
                $this->warn("Gagal kirim ke {$uid}");
            }
        }

        $this->info("Notifikasi selesai. users_sent={$sent} failed={$skipped} piutang={$receivables->count()} utang={$payables->count()}");

        return self::SUCCESS;
    }

    private function receivableLine(BotSocialReceivable $row): string
    {
        $name = trim((string) $row->counterparty_name) ?: 'seseorang';
        $due = $row->expected_back_at?->format('j/n/Y') ?: '-';
        $amount = number_format((int) $row->amount, 0, ',', '.');

        return "• Piutang ke {$name} Rp{$amount} — jatuh tempo {$due} (saatnya ditagih)";
    }

    private function payableLine(BotSocialPayable $row): string
    {
        $name = trim((string) $row->counterparty_name) ?: 'seseorang';
        $due = $row->expected_back_at?->format('j/n/Y') ?: '-';
        $amount = number_format((int) $row->amount, 0, ',', '.');

        return "• Utang ke {$name} Rp{$amount} — jatuh tempo {$due} (saatnya dibayar)";
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BotSocialReceivable>  $receivables
     * @param  \Illuminate\Support\Collection<int, BotSocialPayable>  $payables
     */
    private function markNotified(int $uid, $receivables, $payables): void
    {
        $now = now();
        foreach ($receivables as $row) {
            if ((int) $row->telegram_user_id === $uid) {
                $row->update(['due_notified_at' => $now]);
            }
        }
        foreach ($payables as $row) {
            if ((int) $row->telegram_user_id === $uid) {
                $row->update(['due_notified_at' => $now]);
            }
        }
    }
}
