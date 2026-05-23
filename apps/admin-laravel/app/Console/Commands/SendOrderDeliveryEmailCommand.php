<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderDeliveryMailer;
use App\Support\TelegramBotUrl;
use Illuminate\Console\Command;

class SendOrderDeliveryEmailCommand extends Command
{
    protected $signature = 'order:send-delivery-email
                            {order_code : Kode order, contoh YFD-IVYZWN1WOQ}
                            {--force : Kirim ulang meskipun sudah pernah dikirim}';

    protected $description = 'Kirim email pembeli: link bot Telegram, lisensi, dan Google Sheet';

    public function handle(OrderDeliveryMailer $mailer): int
    {
        $code = strtoupper(trim((string) $this->argument('order_code')));
        $order = Order::with('license')->where('order_code', $code)->first();

        if (! $order) {
            $this->error("Order tidak ditemukan: {$code}");

            return self::FAILURE;
        }

        $botUrl = TelegramBotUrl::resolve();
        $this->table(['Cek', 'Nilai'], [
            ['Order', $order->order_code],
            ['Email', $order->email],
            ['Status', $order->status],
            ['Lisensi', $order->license?->license_key ?? '(belum ada)'],
            ['Google Sheet', $order->spreadsheet_url ?: ($order->spreadsheet_id ? 'ID: '.$order->spreadsheet_id : '(belum ada)')],
            ['Tautan bot', $botUrl ?: '(belum di-set — TELEGRAM_BOT_USERNAME atau telegram.bot_url)'],
            ['MAIL_MAILER', (string) config('mail.default')],
            ['MAIL_FROM', (string) config('mail.from.address')],
        ]);

        if (! $botUrl) {
            $this->warn('Tautan bot kosong — email tetap dikirim tetapi bagian bot akan kosong.');
        }

        if ($order->purchase_delivery_sent_at && ! $this->option('force')) {
            $this->warn('Email sudah pernah dikirim ('.$order->purchase_delivery_sent_at.'). Pakai --force untuk kirim ulang.');

            return self::FAILURE;
        }

        try {
            $mailer->send($order);
        } catch (\Throwable $e) {
            $this->error('Gagal kirim: '.$e->getMessage());
            $this->line('Cek MAIL_* di .env dan log storage/logs/laravel.log');

            return self::FAILURE;
        }

        if (! $order->purchase_delivery_sent_at) {
            $order->update(['purchase_delivery_sent_at' => now()]);
        }

        $this->info("Email terkirim ke {$order->email}");

        return self::SUCCESS;
    }
}
