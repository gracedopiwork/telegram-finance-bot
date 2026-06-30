<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\TelegramBotUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaidOrderDeliveredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akses bot, lisensi & dashboard web — '.$this->order->order_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.paid-order-delivered',
            with: [
                'telegramBotUrl' => TelegramBotUrl::resolve(),
                'telegramBotAppUrl' => TelegramBotUrl::appDeepLink(),
                'telegramBotUsername' => TelegramBotUrl::username(),
            ],
        );
    }
}
