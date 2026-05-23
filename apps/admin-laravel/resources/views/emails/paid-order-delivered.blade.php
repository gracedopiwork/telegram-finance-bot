@php
    $licenseKey = $order->license?->license_key ?? '';
    $sheetHref = $order->spreadsheet_url;
    if (! $sheetHref && $order->spreadsheet_id) {
        $sheetHref = 'https://docs.google.com/spreadsheets/d/' . $order->spreadsheet_id . '/edit';
    }
@endphp
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
            Tautan bot belum diatur di server. Hubungi tim YFD untuk link bot resmi.
        </p>
    @endif

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">2) Aktivasi lisensi</h2>
    @if($licenseKey !== '')
        <p style="font-size: 1.1rem; font-weight: 700; letter-spacing: 0.04em;">{{ $licenseKey }}</p>
        <p>Di dalam chat bot, kirim persis baris berikut (bisa copy-paste):</p>
        <p style="background: #f4f4f5; padding: 12px 16px; border-radius: 8px; font-family: ui-monospace, monospace; font-size: 0.9rem;">/activate {{ $licenseKey }}</p>
        <p style="font-size: 0.875rem; color: #52525b;">Setelah aktif, Anda bisa memakai fitur catat transaksi, <strong>/sheet</strong>, voice note, dan lainnya.</p>
    @else
        <p style="font-size: 0.875rem; color: #52525b;">Kode lisensi sedang disiapkan. Cek halaman sukses pembayaran atau hubungi support.</p>
    @endif

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">3) Google Sheet Anda</h2>
    @if($sheetHref)
        <p>
            <a href="{{ $sheetHref }}" style="display: inline-block; background: #16a34a; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 600;">Buka Google Sheet</a>
        </p>
        <p style="font-size: 0.875rem; color: #52525b; word-break: break-all;">{{ $sheetHref }}</p>
        <p style="font-size: 0.875rem; color: #52525b;">
            Login ke Google dengan <strong>{{ $order->email }}</strong> (email yang Anda isi saat checkout), lalu buka tautan di atas.
            Link ini juga bisa ditampilkan lagi di bot dengan perintah <strong>/sheet</strong> setelah aktivasi.
        </p>
    @else
        <p style="font-size: 0.875rem; color: #52525b;">Spreadsheet sedang disiapkan. Coba lagi nanti perintah <strong>/sheet</strong> di bot, atau hubungi support.</p>
    @endif

    <p style="margin-top: 2rem; font-size: 0.8125rem; color: #71717a;">Email otomatis dari YFD. Mohon tidak membalas ke alamat pengirim.</p>
</body>
</html>
