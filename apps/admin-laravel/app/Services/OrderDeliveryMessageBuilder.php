<?php

namespace App\Services;

use App\Models\Order;
use App\Support\TelegramBotUrl;

class OrderDeliveryMessageBuilder
{
    public function sheetUrl(Order $order): ?string
    {
        if ($order->spreadsheet_url) {
            return $order->spreadsheet_url;
        }

        if ($order->spreadsheet_id) {
            return 'https://docs.google.com/spreadsheets/d/'.$order->spreadsheet_id.'/edit';
        }

        return null;
    }

    public function whatsAppText(Order $order): string
    {
        $order->loadMissing('license');

        $licenseKey = trim((string) ($order->license?->license_key ?? ''));
        $botUrl = TelegramBotUrl::resolve() ?? '';
        $sheetUrl = $this->sheetUrl($order);

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

        if ($sheetUrl) {
            $lines[] = '📊 *Google Sheet Anda*';
            $lines[] = $sheetUrl;
            $lines[] = 'Login dengan Gmail: *'.$order->email.'*';
            $lines[] = 'Setelah aktivasi, ketik /sheet di bot untuk buka lagi.';
        } else {
            $lines[] = '📊 *Google Sheet* sedang disiapkan.';
            $lines[] = 'Link spreadsheet akan dikirim ke WhatsApp ini otomatis (±1–5 menit) setelah siap.';
        }

        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }

    public function whatsAppSheetReadyText(Order $order): string
    {
        $order->loadMissing('license');

        $sheetUrl = $this->sheetUrl($order);
        if ($sheetUrl === null) {
            throw new \RuntimeException('Spreadsheet belum ada untuk order '.$order->order_code);
        }

        $licenseKey = trim((string) ($order->license?->license_key ?? ''));
        $botUrl = TelegramBotUrl::resolve() ?? '';

        $lines = [
            'Hai '.$order->full_name.',',
            '',
            '📊 *Google Sheet Anda sudah siap* (order '.$order->order_code.')',
            $sheetUrl,
            '',
            'Buka dengan Gmail: *'.$order->email.'*',
        ];

        if ($botUrl !== '') {
            $lines[] = '';
            $lines[] = '🤖 Bot Telegram: '.$botUrl;
        }

        if ($licenseKey !== '') {
            $lines[] = '🔑 Aktivasi: /activate '.$licenseKey;
        }

        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }
}
