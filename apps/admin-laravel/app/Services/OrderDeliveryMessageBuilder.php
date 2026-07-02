<?php

namespace App\Services;

use App\Models\Order;
use App\Support\TelegramBotUrl;

class OrderDeliveryMessageBuilder
{
    public function whatsAppText(Order $order): string
    {
        $order->loadMissing(['license', 'digitalProduct']);
        $onboarding = app(PortalOnboardingService::class);

        if ($onboarding->isFtsaUnlockOrder($order)) {
            if ($onboarding->isFtsaUpgradeOrder($order)) {
                return $this->whatsAppFtsaUpgradeText($order);
            }

            return $this->whatsAppFtsaOnlyText($order);
        }

        if ($onboarding->isBotAfterFtsaOrder($order)) {
            return $this->whatsAppBotAfterFtsaText($order);
        }

        return $this->whatsAppFullLicenseText($order, ftsaUnlocked: false);
    }

    private function whatsAppFullLicenseText(Order $order, bool $ftsaUnlocked): string
    {
        $licenseKey = trim((string) ($order->license?->license_key ?? ''));
        $botUrl = TelegramBotUrl::resolve() ?? '';
        $portalUrl = rtrim((string) config('app.url'), '/').'/portal/login';
        $checkupUrl = rtrim((string) config('app.url'), '/').'/check-up';

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
        $lines[] = 'Isi *Financial Health Check-Up* (diagnostik gratis):';
        $lines[] = $checkupUrl;
        if ($ftsaUnlocked) {
            $lines[] = '';
            $lines[] = '✨ *FTSA 1–32* sudah aktif — isi di menu Baseline Data setelah check-up.';
        }

        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }

    public function whatsAppFtsaUpgradeText(Order $order): string
    {
        $portalUrl = rtrim((string) config('app.url'), '/').'/portal/login';
        $licenseKey = trim((string) ($order->license?->license_key ?? ''));

        $lines = [
            'Hai '.$order->full_name.',',
            '',
            'Pembayaran *'.$order->order_code.'* (FTSA Premium) sudah kami terima.',
            '',
            'FTSA 1–32 di portal YFD sudah aktif selama *12 bulan evaluasi*.',
            'Dashboard bot & transaksi tetap seperti biasa.',
            '',
            '📊 Login portal: '.$portalUrl,
            'Gunakan email checkout: *'.$order->email.'*',
        ];

        if ($licenseKey !== '') {
            $lines[] = 'Kode lisensi bot Anda (tetap sama): *'.$licenseKey.'*';
            $lines[] = 'Tidak perlu /activate ulang jika bot sudah aktif.';
        }

        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }

    public function whatsAppBotAfterFtsaText(Order $order): string
    {
        $licenseKey = trim((string) ($order->license?->license_key ?? ''));
        $botUrl = TelegramBotUrl::resolve() ?? '';
        $portalUrl = rtrim((string) config('app.url'), '/').'/portal/login';

        $lines = [
            'Hai '.$order->full_name.',',
            '',
            'Pembayaran *'.$order->order_code.'* (YFD Bot Telegram) sudah kami terima.',
            '',
            'Lisensi Anda *tetap sama* dengan pembelian FTSA — tidak ada kode baru.',
            'Data FTSA & diagnostik di portal ikut terhubung setelah aktivasi bot.',
            '',
        ];

        if ($botUrl !== '') {
            $lines[] = '🤖 *Bot Telegram*';
            $lines[] = $botUrl;
            $lines[] = '';
        }

        if ($licenseKey !== '') {
            $lines[] = '🔑 *Aktivasi bot* (kode sama dengan FTSA):';
            $lines[] = '/activate '.$licenseKey;
            $lines[] = '';
        }

        $lines[] = '📊 Portal: '.$portalUrl;
        $lines[] = 'Email checkout: *'.$order->email.'*';
        $lines[] = 'Setelah /activate, login portal atau ketik */web* di bot.';
        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }

    private function whatsAppFtsaOnlyText(Order $order): string
    {
        $licenseKey = trim((string) ($order->license?->license_key ?? ''));
        $portalUrl = rtrim((string) config('app.url'), '/').'/portal/login';
        $checkupUrl = rtrim((string) config('app.url'), '/').'/check-up';

        $lines = [
            'Hai '.$order->full_name.',',
            '',
            'Pembayaran *'.$order->order_code.'* (FTSA Premium) sudah kami terima.',
            '',
            'Akses *dashboard FTSA* aktif selama *12 bulan evaluasi*.',
            '(Dashboard transaksi bot tidak termasuk paket ini.)',
            '',
        ];

        if ($licenseKey !== '') {
            $lines[] = '🔑 Kode lisensi portal: *'.$licenseKey.'*';
            $lines[] = '';
        }

        $lines[] = '📊 Login portal FTSA: '.$portalUrl;
        $lines[] = 'Email checkout: *'.$order->email.'* + kode lisensi di atas.';
        $lines[] = 'Tidak perlu aktivasi di bot Telegram.';
        $lines[] = '';
        $lines[] = '🩺 Langkah pertama: *Financial Health Check-Up*';
        $lines[] = $checkupUrl;
        $lines[] = 'Lalu isi FTSA 1–32 di menu Baseline Data portal.';
        $lines[] = '';
        $lines[] = '— YFD (Your Financial Doctor)';

        return implode("\n", $lines);
    }

    public function whatsAppSheetReadyText(Order $order): string
    {
        return $this->whatsAppText($order);
    }
}
