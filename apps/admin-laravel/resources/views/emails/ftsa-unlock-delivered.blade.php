@php
    $portalHref = rtrim((string) config('app.url'), '/') . '/portal/login';
    $licenseKey = $order->license?->license_key ?? '';
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

    <p>FTSA 1–32 di portal YFD sekarang <strong>sudah aktif</strong> pada akun lisensi bot Anda yang sama, berlaku <strong>12 bulan evaluasi</strong> sejak pembayaran.</p>

    <p>
        <a href="{{ $portalHref }}" style="display: inline-block; background: {{ config('yfd_brand.navy') }}; color: {{ config('yfd_brand.white') }}; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 600;">Buka Portal YFD</a>
    </p>
    <p style="font-size: 0.875rem; color: #52525b; word-break: break-all;">{{ $portalHref }}</p>

    <p style="font-size: 0.875rem; color: #52525b;">
        Login dengan email checkout <strong>{{ $order->email }}</strong>
        @if($licenseKey !== '')
            dan kode lisensi bot yang sama: <strong>{{ $licenseKey }}</strong>.
        @else
            dan kode lisensi bot Anda yang sudah aktif.
        @endif
        Tidak perlu aktivasi /activate ulang jika bot sudah terhubung.
    </p>

    <p style="margin-top: 2rem; font-size: 0.8125rem; color: #71717a;">Email otomatis dari YFD. Mohon tidak membalas ke alamat pengirim.</p>
</body>
</html>
