<?php

namespace App\Services;

use App\Models\Order;
use App\Support\PhoneNumber;

class OrderWhatsAppDelivery
{
    public function __construct(
        private FonnteClient $fonnte,
        private OrderDeliveryMessageBuilder $messages,
    ) {}

    /**
     * @throws \RuntimeException
     */
    public function send(Order $order): void
    {
        $order->loadMissing('license');

        if ($order->status !== 'paid') {
            throw new \RuntimeException('Order belum lunas.');
        }

        if (! $order->license) {
            throw new \RuntimeException('Order belum punya lisensi.');
        }

        $phone = trim((string) $order->phone);
        if ($phone === '') {
            throw new \RuntimeException('Nomor WhatsApp checkout kosong.');
        }

        $target = PhoneNumber::normalizeIndonesia($phone);
        if (! PhoneNumber::isValidIndonesiaMobile($target)) {
            throw new \RuntimeException('Nomor WhatsApp tidak valid: '.$phone);
        }

        $this->fonnte->sendText($target, $this->messages->whatsAppText($order));
    }
}
