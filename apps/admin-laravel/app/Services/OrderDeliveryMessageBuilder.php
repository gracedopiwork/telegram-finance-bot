<?php

namespace App\Services;

use App\Models\Order;
use App\Support\TelegramBotUrl;

class OrderDeliveryMessageBuilder
{
    public function whatsAppText(Order $order): string
    {
        $order->loadMissing('license');

        $licenseKey = trim((string) ($order->license?->license_key ?? ''));
        $botUrl = TelegramBotUrl::resolve() ?? '';
        $portalUrl = rtrim((string) config('app.url'), '/').'/portal/login';

        $lines = [
            'Hai '.$order->full_name.',',
            '',
            'Pembayaran order *'.$order->order_code.'* sudah kami terima.',
            'Total: *'.$order->amountLabel().'*',
            '',
        ];

        if ($botUrl !== '') {
            $lines[] = '🤖 *Bot Telegram*';
            $lines[] = $botUrl;
            $lines[] = '';
        } else {
            $lines[] = '⚠️ Tautan bot belum diatur di server. Hubungi tim YFD.';
            $lines[] = '';
        }

        if ($licenseKey !== '') {
            $lines[] = '🔑 *Aktivasi lisensi* (copy ke chat bot):';
            $lines[] = '/activate '.$licenseKey;
            $lines[] = '';
        } else {
            $lines[] = '🔑 Kode lisensi sedang disiapkan. Cek halaman sukses pembayaran.';
            $lines[] = '';
        }

        $lines[] = '📊 *Dashboard Web YFD*';
        $lines[] = $portalUrl;
        $lines[] = 'Login pakai email checkout: *'.$order->email.'* + kode lisensi di atas.';

        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }

    public function whatsAppSheetReadyText(Order $order): string
    {
        return $this->whatsAppText($order);
    }
}
