<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Konfirmasi ke pembeli setelah booking konsultasi lunas: kode, produk, jadwal, CTA WA admin. */
class ConsultationPaidMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $whatsAppUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking konsultasi lunas — '.($this->order->consultationSlot?->booking_code ?? $this->order->order_code),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.consultation-paid',
            with: ['slot' => $this->order->consultationSlot],
        );
    }
}
