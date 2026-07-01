<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FtsaUnlockDeliveredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'FTSA Premium aktif — '.$this->order->order_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ftsa-unlock-delivered',
        );
    }
}
