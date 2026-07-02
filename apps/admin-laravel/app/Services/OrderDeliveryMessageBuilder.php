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

            return $this->whatsAppFullLicenseText($order, ftsaUnlocked: true);
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
            'FTSA 1–32 di portal YFD sudah aktif untuk akun Anda.',
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

    public function whatsAppSheetReadyText(Order $order): string
    {
        return $this->whatsAppText($order);
    }
}
