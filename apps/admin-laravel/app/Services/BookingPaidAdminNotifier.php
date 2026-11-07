<?php

namespace App\Services;

use App\Jobs\SendBookingAdminNotificationJob;
use App\Models\ConsultationSlot;
use App\Models\Order;

/**
 * Notif "BOOKING LUNAS" ke tim YFD. Tidak mengirim apa pun sendiri: setiap kanal × penerima
 * menjadi satu job queue, supaya SMTP/Telegram/Fonnte yang lambat tidak menahan request
 * (webhook Pivot atau halaman sukses pembeli) dan retry tidak mengirim ulang ke penerima lain.
 */
class BookingPaidAdminNotifier
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_TELEGRAM = 'telegram';

    public const CHANNEL_WA = 'wa';

    /** @var array<string, string> kanal → key daftar penerima di config services.booking_notify */
    private const RECIPIENT_KEYS = [
        self::CHANNEL_EMAIL => 'emails',
        self::CHANNEL_TELEGRAM => 'telegram_ids',
        self::CHANNEL_WA => 'wa',
    ];

    /**
     * @return int jumlah job yang di-dispatch
     */
    public function dispatchFor(Order $order): int
    {
        $count = 0;
        foreach ((array) config('services.booking_notify.channels', []) as $channel) {
            $key = self::RECIPIENT_KEYS[$channel] ?? null;
            if ($key === null) {
                continue;
            }

            foreach ((array) config("services.booking_notify.{$key}", []) as $recipient) {
                SendBookingAdminNotificationJob::dispatch($order->id, $channel, (string) $recipient);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Teks polos (tanpa Markdown) — sama untuk email, Telegram, dan WA.
     */
    public function message(Order $order): string
    {
        $slot = $order->consultationSlot;

        $lines = [
            '✅ BOOKING LUNAS — siap dijadwalkan',
            '',
            'Kode order: '.$order->order_code,
            'Kode booking: '.($slot?->booking_code ?? '—'),
            'Nominal: '.$order->amountLabel(),
            '',
            'Nama: '.($slot?->guest_name ?: $order->full_name),
            'Email: '.$order->email,
            'No. WA: '.(($slot?->guest_phone ?: $order->phone) ?: '-'),
        ];

        if ($slot) {
            $lines[] = 'Layanan: '.$slot->serviceLabel();
            $lines[] = 'Jadwal: '.$slot->labelSchedule();
            $lines[] = 'Dokter: '.($slot->advisor?->name ?? 'Dokter YFD');
            if ($slot->financial_stage) {
                $lines[] = 'Tahap finansial: '.$slot->financial_stage;
            }
            if ($slot->situation) {
                $lines[] = 'Kondisi: '.$slot->situation;
            }
            if ($slot->notes) {
                $lines[] = '';
                $lines[] = 'Cerita / keluhan:';
                $lines[] = $slot->notes;
            }
        }

        $lines[] = '';
        $lines[] = 'Pembayaran sudah diverifikasi sistem. Tidak perlu konfirmasi transfer manual.';
        $lines[] = '';
        $lines[] = 'Tindak lanjut admin: kirim 2 link form screening ke klien. Klien wajib isi paling lambat '
            .ConsultationSlot::INTAKE_FORM_HOURS.' jam sebelum sesi.';

        return implode("\n", $lines);
    }
}
