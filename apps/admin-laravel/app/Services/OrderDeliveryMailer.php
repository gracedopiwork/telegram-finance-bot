<?php

namespace App\Services;

use App\Mail\PaidOrderDeliveredMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderDeliveryMailer
{
    /**
     * Email berisi: tautan bot Telegram, kode lisensi + /activate, link Google Sheet.
     *
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

        if (trim((string) $order->email) === '') {
            throw new \RuntimeException('Email checkout kosong.');
        }

        Mail::to($order->email)->send(new PaidOrderDeliveredMail($order));
    }
}
