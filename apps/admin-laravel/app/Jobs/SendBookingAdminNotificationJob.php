<?php

namespace App\Jobs;

use App\Mail\BookingPaidAdminMail;
use App\Models\Order;
use App\Services\BookingPaidAdminNotifier;
use App\Services\FonnteClient;
use App\Services\TelegramBotClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Satu notif "BOOKING LUNAS" ke satu penerima di satu kanal. Dijalankan worker yfd-queue,
 * di luar request pembeli. Gagal → di-retry; habis retry → log error (failed_jobs).
 */
class SendBookingAdminNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 5;

    /** @var list<int> detik antar percobaan */
    public array $backoff = [30, 120, 600, 1800];

    public function __construct(
        public int $orderId,
        public string $channel,
        public string $recipient,
    ) {
        // Worker produksi menjalankan `queue:work database`; jangan ikut QUEUE_CONNECTION=sync
        // yang akan mengirim di dalam request lagi.
        $this->onConnection('database');
    }

    public function handle(BookingPaidAdminNotifier $notifier, TelegramBotClient $telegram, FonnteClient $fonnte): void
    {
        $order = Order::with(['consultationSlot.advisor'])->find($this->orderId);
        if (! $order) {
            return;
        }

        $text = $notifier->message($order);

        match ($this->channel) {
            BookingPaidAdminNotifier::CHANNEL_EMAIL => Mail::to($this->recipient)->send(new BookingPaidAdminMail($order, $text)),
            BookingPaidAdminNotifier::CHANNEL_TELEGRAM => $this->sendTelegram($telegram, $text),
            BookingPaidAdminNotifier::CHANNEL_WA => $this->sendWa($fonnte, $text),
            default => Log::warning('Notif admin booking: kanal tidak dikenal', ['channel' => $this->channel]),
        };
    }

    public function failed(?\Throwable $e): void
    {
        Log::error('Notif admin booking gagal permanen', [
            'order_id' => $this->orderId,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'error' => $e?->getMessage(),
        ]);
    }

    private function sendTelegram(TelegramBotClient $telegram, string $text): void
    {
        if (! $telegram->configured()) {
            // Salah konfigurasi tidak sembuh dengan retry.
            Log::warning('Notif admin booking dilewati: TELEGRAM_BOT_TOKEN kosong', ['order_id' => $this->orderId]);

            return;
        }

        if (! $telegram->sendMessage((int) $this->recipient, $text)) {
            throw new \RuntimeException('Telegram sendMessage gagal ke '.$this->recipient);
        }
    }

    private function sendWa(FonnteClient $fonnte, string $text): void
    {
        if (trim((string) config('services.fonnte.token', '')) === '') {
            Log::warning('Notif admin booking dilewati: FONNTE_TOKEN kosong', ['order_id' => $this->orderId]);

            return;
        }

        $fonnte->sendText($this->recipient, $text);
    }
}
