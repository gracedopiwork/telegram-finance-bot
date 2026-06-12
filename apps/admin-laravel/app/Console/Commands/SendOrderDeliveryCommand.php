<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderDeliveryNotifier;
use App\Support\TelegramBotUrl;
use Illuminate\Console\Command;

class SendOrderDeliveryCommand extends Command
{
    protected $signature = 'order:send-delivery
                            {order_code : Kode order, contoh YFD-IVYZWN1WOQ}
                            {--force : Kirim ulang meskipun sudah pernah dikirim}';

    protected $description = 'Kirim ringkasan pembeli (WA/email): link bot Telegram, lisensi, dan Google Sheet';

    public function handle(OrderDeliveryNotifier $notifier): int
    {
        $code = strtoupper(trim((string) $this->argument('order_code')));
        $order = Order::with('license')->where('order_code', $code)->first();

        if (! $order) {
            $this->error("Order tidak ditemukan: {$code}");

            return self::FAILURE;
        }

        $botUrl = TelegramBotUrl::resolve();
        $channels = $notifier->enabledChannels();

        $this->table(['Cek', 'Nilai'], [
            ['Order', $order->order_code],
            ['Channel', implode(', ', $channels) ?: '(tidak valid)'],
            ['Email', $order->email],
            ['WhatsApp', $order->phone ?: '(kosong)'],
            ['Status', $order->status],
            ['Lisensi', $order->license?->license_key ?? '(belum ada)'],
            ['Google Sheet', $order->spreadsheet_url ?: ($order->spreadsheet_id ? 'ID: '.$order->spreadsheet_id : '(belum ada)')],
            ['Tautan bot', $botUrl ?: '(belum di-set)'],
            ['FONNTE_TOKEN', config('services.fonnte.token') ? '(terisi)' : '(kosong)'],
            ['MAIL_MAILER', (string) config('mail.default')],
        ]);

        if ($channels === []) {
            $this->error('ORDER_DELIVERY_CHANNEL tidak valid. Pakai: wa, email, both.');

            return self::FAILURE;
        }

        if (! $botUrl) {
            $this->warn('Tautan bot kosong — pesan tetap dikirim tetapi bagian bot akan kosong.');
        }

        if ($order->purchase_delivery_sent_at && ! $this->option('force')) {
            $this->warn('Sudah pernah dikirim ('.$order->purchase_delivery_sent_at.'). Pakai --force untuk kirim ulang.');

            return self::FAILURE;
        }

        try {
            $notifier->send($order);
        } catch (\Throwable $e) {
            $this->error('Gagal kirim: '.$e->getMessage());
            $this->line('Cek FONNTE_TOKEN / MAIL_* di .env dan storage/logs/laravel.log');

            return self::FAILURE;
        }

        if (! $order->purchase_delivery_sent_at) {
            $order->update(['purchase_delivery_sent_at' => now()]);
        }

        $dest = in_array('wa', $channels, true) ? "WA {$order->phone}" : $order->email;
        $this->info("Ringkasan terkirim ke {$dest}");

        return self::SUCCESS;
    }
}
