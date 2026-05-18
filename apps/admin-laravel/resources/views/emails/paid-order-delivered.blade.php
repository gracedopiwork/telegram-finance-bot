<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembelian berhasil</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Hai {{ $order->full_name }},</p>
    <p>Terima kasih. Pembayaran order <strong>{{ $order->order_code }}</strong> sudah kami terima. Berikut yang Anda butuhkan:</p>

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">1) Buka bot Telegram</h2>
    @if(!empty($telegramBotUrl))
        <p>
            <a href="{{ $telegramBotUrl }}" style="display: inline-block; background: #229ed9; color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 10px; font-weight: 600;">Buka bot di Telegram</a>
        </p>
        <p style="font-size: 0.875rem; color: #52525b;">Atau salin tautan: <span style="word-break: break-all;">{{ $telegramBotUrl }}</span></p>
    @else
        <p style="font-size: 0.875rem; color: #b45309; background: #fffbeb; padding: 12px 14px; border-radius: 8px;">
            Tautan bot belum diatur di server (env <code>TELEGRAM_BOT_URL</code> atau <code>TELEGRAM_BOT_USERNAME</code>, atau pengaturan <code>telegram.bot_url</code> di admin). Hubungi penyedia layanan untuk tautan bot resmi.
        </p>
    @endif

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">2) Aktivasi lisensi</h2>
    <p style="font-size: 1.1rem; font-weight: 700; letter-spacing: 0.04em;">{{ $order->license->license_key }}</p>
    <p>Di dalam chat bot, kirim persis baris berikut (bisa copy-paste):</p>
    <p style="background: #f4f4f5; padding: 12px 16px; border-radius: 8px; font-family: ui-monospace, monospace; font-size: 0.9rem;">/activate {{ $order->license->license_key }}</p>
    <p style="font-size: 0.875rem; color: #52525b;">Setelah aktif, Anda bisa memakai fitur catat transaksi, <strong>/sheet</strong>, dan lainnya.</p>

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">3) Google Sheet Anda</h2>
    @if($order->spreadsheet_url)
        <p><a href="{{ $order->spreadsheet_url }}" style="color: #2563eb; font-weight: 600;">Buka spreadsheet</a></p>
        <p style="font-size: 0.875rem; color: #52525b;">
            Login ke Google dengan <strong>{{ $order->email }}</strong> (email yang Anda isi saat checkout), lalu buka tautan di atas.
            Link ini juga bisa ditampilkan lagi di bot dengan perintah <strong>/sheet</strong> setelah aktivasi.
        </p>
    @else
        <p style="font-size: 0.875rem; color: #52525b;">Spreadsheet sedang disiapkan. Coba lagi nanti perintah <strong>/sheet</strong> di bot, atau hubungi support.</p>
    @endif

    <p style="margin-top: 2rem; font-size: 0.8125rem; color: #71717a;">Email otomatis. Mohon tidak membalas ke alamat pengirim.</p>
</body>
</html>
