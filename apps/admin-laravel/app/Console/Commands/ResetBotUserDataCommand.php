<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetBotUserDataCommand extends Command
{
    protected $signature = 'bot:reset-data
                            {--with-orders : Hapus juga orders, payment_events, dan licenses}
                            {--force : Lewati konfirmasi}';

    protected $description = 'Hapus semua data bot Telegram (transaksi, baseline, aktivasi) tanpa menyentuh konten website';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Hapus SEMUA data transaksi, diagnostik, dan aktivasi bot? Website tidak terpengaruh.')) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $withOrders = (bool) $this->option('with-orders');

        DB::transaction(function () use ($withOrders): void {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            if ($withOrders) {
                $this->truncateIfExists('payment_events');
                $this->truncateIfExists('orders');
                $this->truncateIfExists('licenses');
            } else {
                $this->truncateIfExists('license_activations');
                DB::table('licenses')->update([
                    'assigned_user_id' => null,
                    'assigned_username' => null,
                    'activated_at' => null,
                ]);
            }

            $this->truncateIfExists('bot_transactions');
            $this->truncateIfExists('financial_baselines');
            $this->truncateIfExists('user_ai_usage');
            $this->truncateIfExists('bot_ai_daily_stats');
            $this->truncateIfExists('user_sheets');
            $this->truncateIfExists('transactions');

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        $this->info('Data bot berhasil direset.');
        $this->line('Yang dihapus: transaksi bot, baseline/diagnostik, kuota AI, aktivasi lisensi.');
        $this->line('ID baris baru akan mulai dari 1 lagi (TRUNCATE).');
        $this->line('Yang TIDAK disentuh: artikel, paket, settings website, produk digital, admin.');

        if ($withOrders) {
            $this->line('Orders & lisensi juga dihapus (--with-orders).');
        } else {
            $this->line('Orders & kode lisensi tetap ada — user perlu /activate ulang di bot.');
        }

        $this->newLine();
        $this->comment('Restart bot Python setelah reset: systemctl restart ... atau stop/start manual.');

        return self::SUCCESS;
    }

    private function truncateIfExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $count = DB::table($table)->count();
        DB::table($table)->truncate();

        $this->line("  · {$table}: {$count} baris dihapus (ID reset ke 1)");
    }
}
