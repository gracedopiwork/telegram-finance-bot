<?php

namespace App\Jobs;

use App\Mail\ConsultationPaidMail;
use App\Models\Order;
use App\Services\ConsultationSlotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email konfirmasi ke pembeli setelah booking konsultasi lunas. purchase_delivery_sent_at
 * hanya diisi bila email benar-benar terkirim; gagal → retry, habis retry → tetap null
 * (halaman sukses menampilkan "Menunggu…", admin bisa kirim ulang).
 */
class SendConsultationPaidEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 5;

    /** @var list<int> detik antar percobaan */
    public array $backoff = [30, 120, 600, 1800];

    public function __construct(public int $orderId)
    {
        // Lihat SendBookingAdminNotificationJob: selalu ke worker, tidak pernah inline.
        $this->onConnection('database');
    }

    public function handle(ConsultationSlotService $slots): void
    {
        $order = Order::with(['consultationSlot.advisor'])->find($this->orderId);
        if (! $order || $order->status !== 'paid' || ! $order->consultationSlot || trim((string) $order->email) === '') {
            return;
        }

        Mail::to($order->email)->send(new ConsultationPaidMail(
            $order,
            $slots->paidGuestWhatsAppUrl($order->consultationSlot, $order),
        ));

        Order::whereKey($order->id)->update(['purchase_delivery_sent_at' => now()]);
    }

    public function failed(?\Throwable $e): void
    {
        Log::error('Email konfirmasi booking ke pembeli gagal permanen', [
            'order_id' => $this->orderId,
            'error' => $e?->getMessage(),
        ]);
    }
}
