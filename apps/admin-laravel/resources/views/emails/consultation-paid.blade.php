<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking konsultasi lunas</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Hai {{ $order->full_name }},</p>
    <p>Terima kasih. @include('partials.consultation-paid-instruction')</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: 0.9375rem;">
        <tr><td style="padding: 6px 0; color: #52525b; width: 130px;">Kode Order</td><td style="padding: 6px 0; font-weight: 700;">{{ $order->order_code }}</td></tr>
        @if($slot)
            <tr><td style="padding: 6px 0; color: #52525b;">Kode Booking</td><td style="padding: 6px 0; font-weight: 700;">{{ $slot->booking_code }}</td></tr>
        @endif
        <tr><td style="padding: 6px 0; color: #52525b;">Produk</td><td style="padding: 6px 0;">{{ $order->product_name }}</td></tr>
        @if($slot)
            <tr><td style="padding: 6px 0; color: #52525b;">Jadwal</td><td style="padding: 6px 0; font-weight: 600;">{{ $slot->labelSchedule() }}</td></tr>
            @if($slot->advisor)
                <tr><td style="padding: 6px 0; color: #52525b;">Dokter</td><td style="padding: 6px 0;">{{ $slot->advisor->name }}</td></tr>
            @endif
        @endif
        <tr><td style="padding: 6px 0; color: #52525b;">Total</td><td style="padding: 6px 0;">{{ $order->amountLabel() }}</td></tr>
        <tr><td style="padding: 6px 0; color: #52525b;">Status</td><td style="padding: 6px 0; color: #047857; font-weight: 700;">LUNAS</td></tr>
    </table>

    <p>
        <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" style="display: inline-block; background: #25d366; color: #ffffff !important; text-decoration: none; padding: 14px 22px; border-radius: 10px; font-weight: 600; mso-padding-alt: 14px 22px;">Konfirmasi booking Anda dengan admin kami</a>
    </p>
    <p style="font-size: 0.875rem; color: #52525b;">
        Jika tombol tidak terbuka, salin lalu tempel di browser:<br>
        <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" style="color: #229ed9; word-break: break-all;">{{ $whatsAppUrl }}</a>
    </p>

    <p style="margin-top: 2rem; font-size: 0.8125rem; color: #71717a;">Email otomatis dari YFD. Mohon tidak membalas ke alamat pengirim.</p>
</body>
</html>
