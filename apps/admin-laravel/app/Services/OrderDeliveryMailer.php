<?php

namespace App\Services;

use App\Mail\FtsaUnlockDeliveredMail;
use App\Mail\PaidOrderDeliveredMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderDeliveryMailer
{
    /**
     * Email berisi: tautan bot Telegram, kode lisensi + /activate, dan portal web.
     * Untuk produk FTSA add-on: email khusus unlock FTSA.
     *
     * @throws \RuntimeException
     */
    public function send(Order $order): void
    {
        $order->loadMissing(['license', 'digitalProduct']);

        if ($order->status !== 'paid') {
            throw new \RuntimeException('Order belum lunas.');
        }

        if (! $order->license) {
            throw new \RuntimeException('Order belum punya lisensi.');
        }

        if (trim((string) $order->email) === '') {
            throw new \RuntimeException('Email checkout kosong.');
        }

        $mailable = $this->isFtsaUnlockOrder($order)
            ? new FtsaUnlockDeliveredMail($order)
            : new PaidOrderDeliveredMail($order);

        Mail::to($order->email)->send($mailable);
    }

    private function isFtsaUnlockOrder(Order $order): bool
    {
        $code = $order->digitalProduct?->code ?? $order->plan;

        return in_array($code, (array) config('portal.ftsa.unlock_product_codes', []), true);
    }
}
