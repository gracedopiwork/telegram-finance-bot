<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderDeliveryNotifier
{
    public function __construct(
        private OrderWhatsAppDelivery $whatsApp,
        private OrderDeliveryMailer $mailer,
    ) {}

    /**
     * Kirim ringkasan order (bot, lisensi, sheet) lewat channel yang dikonfigurasi.
     *
     * @return list<string> channel yang berhasil: wa, email
     *
     * @throws \RuntimeException jika semua channel gagal
     */
    public function send(Order $order): array
    {
        $channels = $this->enabledChannels();
        if ($channels === []) {
            throw new \RuntimeException('ORDER_DELIVERY_CHANNEL tidak valid (pakai: wa, email, both).');
        }

        $succeeded = [];
        $errors = [];

        if (in_array('wa', $channels, true)) {
            try {
                $this->whatsApp->send($order);
                $succeeded[] = 'wa';
            } catch (\Throwable $e) {
                $errors[] = 'WA: '.$e->getMessage();
                Log::warning('Pengiriman WA order gagal', [
                    'order_code' => $order->order_code,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        if (in_array('email', $channels, true)) {
            try {
                $this->mailer->send($order);
                $succeeded[] = 'email';
            } catch (\Throwable $e) {
                $errors[] = 'Email: '.$e->getMessage();
                Log::warning('Pengiriman email order gagal', [
                    'order_code' => $order->order_code,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $allRequired = count($succeeded) === count($channels);
        if (! $allRequired) {
            throw new \RuntimeException(implode('; ', $errors) ?: 'Pengiriman gagal.');
        }

        return $succeeded;
    }

    /**
     * @return list<'wa'|'email'>
     */
    public function enabledChannels(): array
    {
        $channel = strtolower(trim((string) config('services.order_delivery.channel', 'wa')));

        return match ($channel) {
            'wa' => ['wa'],
            'email' => ['email'],
            'both' => ['wa', 'email'],
            default => [],
        };
    }

    public function primaryChannelLabel(): string
    {
        $channels = $this->enabledChannels();

        return match (true) {
            $channels === ['wa'] => 'WhatsApp',
            $channels === ['email'] => 'Email',
            $channels === ['wa', 'email'] => 'WhatsApp & Email',
            default => '—',
        };
    }
}
