@php
    $licenseKey = $order->license?->license_key ?? '';
    $portalHref = rtrim((string) config('app.url'), '/') . '/portal/login';
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
        @if(!empty($telegramBotAppUrl))
            <p>
                <a href="{{ $telegramBotAppUrl }}" style="display: inline-block; background: #229ed9; color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 10px; font-weight: 600;">Buka di aplikasi Telegram</a>
            </p>
        @endif
        <p style="font-size: 0.875rem; color: #52525b;">
            Atau buka tautan ini (disarankan jika tombol di atas tidak jalan):<br>
            <a href="{{ $telegramBotUrl }}" style="color: #229ed9; word-break: break-all;">{{ $telegramBotUrl }}</a>
        </p>
        @if(!empty($telegramBotUsername))
            <p style="font-size: 0.8125rem; color: #71717a; margin-top: 0.75rem;">
                Di Telegram, cari bot <strong>@{{ $telegramBotUsername }}</strong> lalu ketuk <strong>Start</strong>.
            </p>
        @endif
    @else
        <p style="font-size: 0.875rem; color: #52525b; background: #f4f4f5; padding: 12px 14px; border-radius: 8px;">
            Buka aplikasi Telegram lalu cari bot YFD Finance Bot, kemudian ketuk <strong>Start</strong>.
            Jika belum ketemu, balas email ini untuk dibantu tim YFD.
        </p>
    @endif

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">2) Aktivasi lisensi</h2>
    @if($licenseKey !== '')
        <p style="font-size: 1.1rem; font-weight: 700; letter-spacing: 0.04em;">{{ $licenseKey }}</p>
        <p>Di dalam chat bot, kirim persis baris berikut (bisa copy-paste):</p>
        <p style="background: #f4f4f5; padding: 12px 16px; border-radius: 8px; font-family: ui-monospace, monospace; font-size: 0.9rem;">/activate {{ $licenseKey }}</p>
        <p style="font-size: 0.875rem; color: #52525b;">Setelah aktif, Anda bisa mulai catat transaksi dan melihat dashboard web YFD.</p>
    @else
        <p style="font-size: 0.875rem; color: #52525b;">Kode lisensi sedang disiapkan. Cek halaman sukses pembayaran atau hubungi support.</p>
    @endif

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">3) Dashboard Web YFD</h2>
    <p>
        <a href="{{ $portalHref }}" style="display: inline-block; background: #003366; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 600;">Buka Dashboard Web</a>
    </p>
    <p style="font-size: 0.875rem; color: #52525b; word-break: break-all;">{{ $portalHref }}</p>
    <p style="font-size: 0.875rem; color: #52525b;">
        Login dengan email checkout <strong>{{ $order->email }}</strong> dan kode lisensi Anda.
        Atau ketik <strong>/web</strong> di bot untuk link masuk otomatis.
    </p>

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">4) Isi Diagnostik (Baseline Data) — wajib</h2>
    @php $baselineHref = rtrim((string) config('app.url'), '/') . '/check-up'; @endphp
    <p style="font-size: 0.875rem; color: #52525b;">
        Setelah masuk dashboard, langkah pertama adalah mengisi <strong>Baseline Data (Diagnostik Keuangan)</strong>.
        Ini menentukan tahap keuangan Anda dan mengaktifkan prescription bucket di dashboard.
    </p>
    <p>
        <a href="{{ $baselineHref }}" style="display: inline-block; background: #dca115; color: #0c2240; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 700;">Isi Diagnostik Sekarang</a>
    </p>
    <p style="font-size: 0.8125rem; color: #71717a;">Menu di portal: <strong>BASELINE DATA (WAJIB DI ISI)</strong> → jawab semua pertanyaan → Simpan.</p>

    <p style="margin-top: 2rem; font-size: 0.8125rem; color: #71717a;">Email otomatis dari YFD. Mohon tidak membalas ke alamat pengirim.</p>
</body>
</html>
