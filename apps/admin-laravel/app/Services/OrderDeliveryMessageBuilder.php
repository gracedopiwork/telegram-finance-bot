<?php

namespace App\Services;

use App\Models\Order;
use App\Support\TelegramBotUrl;

class OrderDeliveryMessageBuilder
{
    public function whatsAppText(Order $order): string
    {
        $order->loadMissing(['license', 'digitalProduct']);
        $code = $order->digitalProduct?->code ?? $order->plan;
        if (in_array($code, (array) config('portal.ftsa.unlock_product_codes', []), true)) {
            return $this->whatsAppFtsaUnlockText($order);
        }

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
        $lines[] = 'Atau ketik */web* di bot untuk link masuk otomatis.';
        $lines[] = '';
        $lines[] = '🩺 *Langkah wajib setelah masuk:*';
        $lines[] = 'Isi *Baseline Data (Diagnostik)* di menu portal:';
        $lines[] = rtrim((string) config('app.url'), '/').'/portal/baseline/baru';

        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }

    public function whatsAppFtsaUnlockText(Order $order): string
    {
        $portalUrl = rtrim((string) config('app.url'), '/').'/portal/baseline/baru';

        return implode("\n", [
            'Hai '.$order->full_name.',',
            '',
            'Pembayaran *'.$order->order_code.'* (FTSA Premium) sudah kami terima.',
            '',
            'FTSA 1–32 di portal YFD sudah aktif untuk akun Anda.',
            '',
            '📊 Buka: '.$portalUrl,
            'Login dengan email checkout: *'.$order->email.'*',
            '',
            '— YFD (Your Financial Doctor)',
        ]);
    }

    public function whatsAppSheetReadyText(Order $order): string
    {
        return $this->whatsAppText($order);
    }
}
