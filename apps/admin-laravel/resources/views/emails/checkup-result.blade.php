@php
    $label = $stageDisplay['label'] ?? $baseline->stage_label ?? '—';
    $phase = $stageDisplay['phase'] ?? '';
    $diagnosis = $stageDisplay['diagnosis'] ?? ($stageDisplay['risk_description'] ?? '');
    $riskLabel = $stageDisplay['risk_label'] ?? null;
    $score = (int) ($baseline->financial_stage_score ?? 0);
    $nextReview = $baseline->formatNextReview('d M Y');
    $assessed = $baseline->formatDate('d M Y H:i');
    $portalHref = url('/portal/login');
    $checkupHref = url('/check-up');
    $navy = config('yfd_brand.navy', '#0B1F3A');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Financial Health Check-Up</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Hai,</p>
    <p>
        Berikut ringkasan <strong>Financial Health Check-Up</strong> Anda di Your Financial Doctor.
        Email ini hanya dikirim ke <strong>{{ $baseline->email }}</strong> — hasil milik Anda sendiri.
    </p>

    <div style="margin: 1.5rem 0; padding: 20px; border-radius: 12px; background: #f0f9ff; border: 1px solid #bae6fd;">
        @if($phase !== '')
            <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #0369a1;">{{ $phase }}</div>
        @endif
        <div style="font-size: 1.75rem; font-weight: 800; margin-top: 4px;">{{ $label }}</div>
        <div style="margin-top: 10px; font-size: 1.05rem; font-weight: 700;">Skor {{ $score }}/39</div>
        @if($riskLabel)
            <p style="margin: 12px 0 0; font-size: 0.9rem;"><strong>{{ $riskLabel }}:</strong> {{ $diagnosis }}</p>
        @elseif($diagnosis !== '')
            <p style="margin: 12px 0 0; font-size: 0.9rem;">{{ $diagnosis }}</p>
        @endif
    </div>

    <p style="font-size: 0.875rem; color: #52525b;">
        Check-up: <strong>{{ $assessed }}</strong><br>
        Evaluasi ulang tersedia mulai: <strong>{{ $nextReview }}</strong>
        (setiap {{ $reviewMonths }} bulan).
    </p>

    <p style="font-size: 0.875rem; color: #52525b;">
        Simpan email ini sebagai referensi. Data Anda tetap tersimpan di dashboard YFD —
        tidak dihapus setelah email terkirim.
    </p>

    <p style="margin-top: 1.75rem;">
        <a href="{{ $portalHref }}" target="_blank" rel="noopener"
           style="display: inline-block; background: {{ $navy }}; color: #ffffff !important; text-decoration: none; padding: 12px 20px; border-radius: 10px; font-weight: 600;">
            Buka Dashboard Web
        </a>
    </p>
    <p style="font-size: 0.8125rem; color: #71717a;">
        Check-up ulang (setelah masa evaluasi):
        <a href="{{ $checkupHref }}" style="color: #0369a1;">{{ $checkupHref }}</a>
    </p>

    <p style="margin-top: 2rem; font-size: 0.8rem; color: #a1a1aa;">
        Your Financial Doctor · Jika Anda tidak meminta check-up ini, abaikan email ini.
    </p>
</body>
</html>
