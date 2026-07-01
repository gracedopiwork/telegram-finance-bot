<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetBotUserDataCommand extends Command
{
    protected $signature = 'bot:reset-data
                            {--with-orders : Hapus juga orders, payment_events, dan licenses}
                            {--force : Lewati konfirmasi}';

    protected $description = 'Hapus semua data bot Telegram (transaksi, baseline, aktivasi) tanpa menyentuh konten website';

    /** @var list<string> */
    private array $botDataTables = [
        'bot_transactions',
        'financial_baselines',
        'user_ai_usage',
        'bot_ai_daily_stats',
        'user_sheets',
        'transactions',
    ];

    public function handle(): int
    {
        if (! $this->option('force')) {
            $withOrders = (bool) $this->option('with-orders');
            $prompt = $withOrders
                ? 'Hapus SEMUA data bot + orders/lisensi di admin Order & Pembayaran? (Website tetap aman)'
                : 'Hapus data bot (catatan, baseline, aktivasi)? Order & Pembayaran di admin TETAP ADA (tambahkan --with-orders untuk hapus juga).';
            if (! $this->confirm($prompt)) {
                $this->warn('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $withOrders = (bool) $this->option('with-orders');

        // TRUNCATE di MySQL melakukan implicit COMMIT — jangan bungkus DB::transaction().
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            if ($withOrders) {
                $this->truncateIfExists('payment_events');
                $this->truncateIfExists('orders');
                $this->truncateIfExists('licenses');
            } else {
                $this->truncateIfExists('license_activations');
                $updated = DB::table('licenses')->update([
                    'assigned_user_id' => null,
                    'assigned_username' => null,
                    'activated_at' => null,
                ]);
                $this->line("  · licenses: {$updated} baris aktivasi di-reset (kode lisensi tetap)");
            }

            foreach ($this->botDataTables as $table) {
                $this->truncateIfExists($table);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $remaining = $this->countRemainingBotRows();
        if ($remaining > 0) {
            $this->error("Masih ada {$remaining} baris data bot. Cek koneksi DB (.env) sama dengan bot Python.");

            return self::FAILURE;
        }

        $this->info('Data bot berhasil direset.');
        $this->line('Yang dihapus: transaksi bot, baseline/diagnostik, kuota AI, aktivasi lisensi.');
        $this->line('ID baris baru akan mulai dari 1 lagi (TRUNCATE).');
        $this->line('Yang TIDAK disentuh: artikel, paket, settings website, produk digital, admin.');

        if ($withOrders) {
            $this->line('Orders & lisensi juga dihapus (--with-orders).');
        } else {
            $orderCount = Order::query()->count();
            $this->line('Orders & kode lisensi tetap ada — user perlu /activate ulang di bot.');
            if ($orderCount > 0) {
                $this->newLine();
                $this->warn("Masih ada {$orderCount} order di admin Order & Pembayaran.");
                $this->warn('Untuk hapus juga: php artisan bot:reset-data --with-orders --force');
            }
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

        $count = (int) DB::table($table)->count();
        DB::statement('TRUNCATE TABLE `'.$table.'`');

        $this->line("  · {$table}: {$count} baris dihapus (ID reset ke 1)");
    }

    private function countRemainingBotRows(): int
    {
        $total = 0;

        foreach (array_merge($this->botDataTables, ['license_activations']) as $table) {
            if (Schema::hasTable($table)) {
                $total += (int) DB::table($table)->count();
            }
        }

        return $total;
    }
}
