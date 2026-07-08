@php
    $licenseKey = $order->license?->license_key ?? '';
    $portalHref = rtrim((string) config('app.url'), '/') . '/portal/login';
    $checkupHref = rtrim((string) config('app.url'), '/') . '/check-up';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FTSA Premium aktif</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Hai {{ $order->full_name }},</p>
    <p>Terima kasih. Pembayaran <strong>{{ $order->product_name ?? 'FTSA Premium Unlock' }}</strong> (order <strong>{{ $order->order_code }}</strong>) sudah kami terima.</p>

    <p>Paket ini membuka <strong>dashboard FTSA</strong> di portal YFD (kuesioner FTSA 1–32, behavioral insight, dan indeks kesehatan finansial) selama <strong>12 bulan evaluasi</strong>.</p>
    <p style="font-size: 0.875rem; color: #52525b;">Dashboard pencatatan transaksi via YFD First Aid <strong>tidak termasuk</strong> dalam paket ini.</p>

    @if($licenseKey !== '')
        <p style="font-size: 1.1rem; font-weight: 700; letter-spacing: 0.04em;">{{ $licenseKey }}</p>
    @endif

    <p>
        <a href="{{ $portalHref }}" style="display: inline-block; background: {{ config('yfd_brand.navy') }}; color: {{ config('yfd_brand.white') }}; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 600;">Login Portal FTSA</a>
    </p>
    <p style="font-size: 0.875rem; color: #52525b; word-break: break-all;">{{ $portalHref }}</p>
    <p style="font-size: 0.875rem; color: #52525b;">
        Login dengan email checkout <strong>{{ $order->email }}</strong> dan kode lisensi di atas.
        <strong>Tidak perlu</strong> aktivasi di YFD First Aid.
    </p>

    <h2 style="font-size: 1rem; margin-top: 1.75rem;">Langkah pertama</h2>
    <p style="font-size: 0.875rem; color: #52525b;">
        Isi <strong>Financial Health Check-Up</strong> lalu lengkapi <strong>FTSA 1–32</strong> di menu Baseline Data portal.
    </p>
    <p>
        <a href="{{ $checkupHref }}" style="display: inline-block; background: {{ config('yfd_brand.gold') }}; color: {{ config('yfd_brand.navy') }}; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 700;">Mulai Check-Up</a>
    </p>

    <p style="margin-top: 2rem; font-size: 0.8125rem; color: #71717a;">Email otomatis dari YFD. Mohon tidak membalas ke alamat pengirim.</p>
</body>
</html>
