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
        $this->sendToOrderPhone($order, $this->messages->whatsAppText($order));
    }

    /**
     * @throws \RuntimeException
     */
    public function sendSheetReady(Order $order): void
    {
        $this->sendToOrderPhone($order, $this->messages->whatsAppSheetReadyText($order));
    }

    /**
     * @throws \RuntimeException
     */
    private function sendToOrderPhone(Order $order, string $message): void
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

        $this->fonnte->sendText($target, $message);
    }
}
