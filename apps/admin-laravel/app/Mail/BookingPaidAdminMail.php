<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Notif internal "BOOKING LUNAS" ke tim YFD — teks polos, isi sama dengan pesan Telegram/WA. */
class BookingPaidAdminMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $text,
    ) {}

    public function envelope(): Envelope
    {
        $slot = $this->order->consultationSlot;

        return new Envelope(
            subject: 'BOOKING LUNAS — '.($slot?->booking_code ?? $this->order->order_code)
                .($slot ? ' · '.$slot->labelSchedule() : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.booking-paid-admin',
            with: ['text' => $this->text],
        );
    }
}
