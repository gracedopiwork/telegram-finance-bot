@extends('Companyprofile.layouts.main')

@section('title', 'Pembayaran Diproses — YFD')

@php
    $deliverySent = $order && $order->purchase_delivery_sent_at !== null;
    $deliveryLabel = $deliveryChannelLabel ?? 'WhatsApp';
    $deliveryViaEmail = $deliveryViaEmail ?? false;
    $deliveryContact = $order
        ? ($deliveryViaEmail ? $order->email : $order->phone)
        : '';
    $isFtsaUpgrade = ($orderContext['is_ftsa_upgrade'] ?? false);
    $isFtsaOnly = ($orderContext['is_ftsa_only'] ?? false);
    $isBotAfterFtsa = ($orderContext['is_bot_after_ftsa'] ?? false);
@endphp

@push('head')
    @if($order && $order->status === 'pending')
        <meta http-equiv="refresh" content="12">
    @endif
@endpush

@section('content')

<section class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-20 md:py-28 text-center">

    <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-emerald-100 grid place-items-center">
        <span class="material-symbols-outlined text-emerald-600 text-[44px]">check_circle</span>
    </div>

    <h1 class="font-heading text-headline-lg text-primary mb-3">Terima kasih!</h1>

    @if($order)
        <p class="text-body-md text-on-surface-variant mb-2">
            Order Anda <strong class="text-primary">{{ $order->order_code }}</strong> sedang kami proses.
        </p>

        @if($order->status === 'paid' && $order->license)
            @if($isFtsaUpgrade)
                <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-6">
                    Pembayaran <strong>lunas</strong>. <strong>FTSA Premium</strong> sudah aktif pada lisensi bot Anda yang sama
                    selama <strong>12 bulan evaluasi</strong>.
                    Login portal dengan email <strong>{{ $order->email }}</strong> dan kode lisensi bot yang sudah pernah di-/activate.
                </p>
            @elseif($isBotAfterFtsa)
                <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-6">
                    Pembayaran <strong>lunas</strong>. <strong>YFD First Aid</strong> aktif pada <strong>lisensi FTSA yang sama</strong>
                    (tidak ada kode baru). Aktifkan di bot dengan <code class="bg-white px-1 rounded">/activate {{ $order->license->license_key }}</code>
                    — data FTSA & diagnostik ikut terhubung.
                </p>
                <div class="bg-primary/5 border-2 border-primary/20 rounded-2xl p-6 max-w-lg mx-auto text-left mb-6">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-primary mb-2">Kode lisensi (sama dengan FTSA)</p>
                    <code id="licenseKeyDisplay" class="block text-base sm:text-lg font-mono font-bold text-primary break-all select-all bg-white px-4 py-3 rounded-xl border border-outline-variant">{{ $order->license->license_key }}</code>
                </div>
            @elseif($isFtsaOnly)
                <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-6">
                    Pembayaran <strong>lunas</strong>. Akses <strong>dashboard FTSA</strong> aktif selama <strong>12 bulan evaluasi</strong>.
                    Login portal dengan email <strong>{{ $order->email }}</strong> dan kode lisensi di bawah — <strong>tanpa aktivasi bot</strong>.
                </p>
                <div class="bg-primary/5 border-2 border-primary/20 rounded-2xl p-6 max-w-lg mx-auto text-left mb-6">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-primary mb-2">Kode lisensi portal FTSA</p>
                    <code id="licenseKeyDisplay" class="block text-base sm:text-lg font-mono font-bold text-primary break-all select-all bg-white px-4 py-3 rounded-xl border border-outline-variant">{{ $order->license->license_key }}</code>
                    <button type="button"
                            class="mt-4 w-full btn btn-primary text-sm"
                            onclick="navigator.clipboard.writeText({{ json_encode($order->license->license_key) }}); this.innerText='Tersalin!';">
                        Salin kode lisensi
                    </button>
                </div>
            @elseif($deliveryViaEmail)
                <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-6">
                    Pembayaran <strong>lunas</strong>. Kode aktivasi{{ $isFtsaOnly ? '' : ' bot' }} dan akses dashboard web sudah dikirim ke email
                    <strong>{{ $order->email }}</strong>. Cek inbox (dan folder Spam) lalu lanjutkan aktivasi.
                </p>
            @else
                <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-4">
                    Pembayaran <strong>lunas</strong>. Simpan kode lisensi di bawah ini — kode ini harus sama persis saat Anda
                    <strong>/activate</strong> di YFD First Aid. Ringkasan juga dikirim ke {{ $deliveryLabel }} <strong>{{ $deliveryContact }}</strong>.
                </p>

                <div class="bg-primary/5 border-2 border-primary/20 rounded-2xl p-6 max-w-lg mx-auto text-left mb-6">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-primary mb-2">Kode lisensi{{ $isFtsaOnly ? '' : ' bot' }}</p>
                    <code id="licenseKeyDisplay" class="block text-base sm:text-lg font-mono font-bold text-primary break-all select-all bg-white px-4 py-3 rounded-xl border border-outline-variant">{{ $order->license->license_key }}</code>
                    <button type="button"
                            class="mt-4 w-full btn btn-primary text-sm"
                            onclick="navigator.clipboard.writeText({{ json_encode($order->license->license_key) }}); this.innerText='Tersalin!';">
                        Salin kode lisensi
                    </button>
                    <p class="text-[12px] text-on-surface-variant mt-4 leading-relaxed">
                        Di Telegram, buka bot lalu kirim (bisa copy-paste):<br>
                        <code class="text-[11px] bg-white px-2 py-1 rounded border border-outline-variant inline-block mt-1 select-all">/activate {{ $order->license->license_key }}</code>
                    </p>
                </div>
            @endif

            @if($order->status === 'paid' && $order->license)
                <div class="bg-primary-container/5 border border-primary-container/20 rounded-2xl p-6 max-w-lg mx-auto text-left mb-6">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-primary mb-3">Langkah selanjutnya</p>
                    <ol class="text-[13px] text-on-surface-variant space-y-3 list-decimal list-inside">
                        @if($isFtsaUpgrade)
                            <li>Login portal: <a href="{{ route('portal.login') }}" class="text-primary font-semibold underline">portal/login</a> dengan email & lisensi bot yang sama</li>
                            <li>Buka menu <strong>BASELINE DATA</strong> untuk mengisi FTSA 1–32</li>
                        @elseif($isBotAfterFtsa)
                            <li>Buka YFD First Aid di Telegram → <code class="bg-white px-1 rounded">/activate {{ $order->license->license_key }}</code> (kode sama dengan FTSA)</li>
                            <li>Login portal: <a href="{{ route('portal.login') }}" class="text-primary font-semibold underline">portal/login</a> atau <code class="bg-white px-1 rounded">/web</code> di YFD First Aid</li>
                            <li>Dashboard lengkap + FTSA — lisensi YFD First Aid berlaku <strong>selamanya</strong></li>
                        @elseif($isFtsaOnly)
                            <li>Login portal: <a href="{{ route('portal.login') }}" class="text-primary font-semibold underline">portal/login</a> dengan email & kode lisensi di atas</li>
                            <li>Di dalam portal: isi <strong>diagnostik keuangan</strong> lalu <strong>FTSA 1–32</strong> (aktif <strong>12 bulan evaluasi</strong>)</li>
                        @else
                            <li>Buka YFD First Aid di Telegram → <code class="bg-white px-1 rounded">/activate {{ $order->license->license_key }}</code></li>
                            <li>Masuk dashboard: <a href="{{ route('portal.login') }}" class="text-primary font-semibold underline">portal/login</a> atau ketik <code class="bg-white px-1 rounded">/web</code> di YFD First Aid</li>
                            <li>Di dalam portal: isi <strong>Baseline Data (diagnostik)</strong> — bukan di landing page</li>
                            <li>Catat transaksi harian di YFD First Aid, pantau dashboard — lisensi berlaku <strong>selamanya</strong></li>
                        @endif
                    </ol>
                    @if($isFtsaUpgrade)
                        <a href="{{ route('portal.login') }}" class="btn btn-primary mt-4 w-full text-sm">Buka Portal</a>
                    @elseif($isBotAfterFtsa)
                        <a href="{{ route('portal.login') }}" class="btn btn-primary mt-4 w-full text-sm">Buka Portal</a>
                    @elseif($isFtsaOnly)
                        <a href="{{ route('portal.login') }}" class="btn btn-primary mt-4 w-full text-sm">Buka Portal</a>
                    @else
                        <a href="{{ route('portal.login') }}" class="btn btn-primary mt-4 w-full text-sm">
                            Buka Portal
                        </a>
                    @endif
                </div>
            @endif
        @else
            <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-8">
                @if($order->status === 'pending')
                    Status pembayaran dikonfirmasi Midtrans dalam beberapa menit. Halaman ini <strong>otomatis dimuat ulang</strong> setiap 12 detik
                    sampai status berubah. Setelah <strong>lunas</strong>, ringkasan dikirim ke email Anda.
                @else
                    Setelah <strong>lunas</strong>, ringkasan dikirim ke {{ $deliveryLabel }} <strong>{{ $deliveryContact }}</strong>.
                @endif
            </p>
        @endif

        <div class="bg-white border border-outline-variant rounded-2xl p-6 max-w-md mx-auto text-left mb-8">
            <dl class="text-[13.5px] space-y-2">
                <div class="flex justify-between"><dt class="text-on-surface-variant">Order Code</dt><dd class="font-semibold">{{ $order->order_code }}</dd></div>
                <div class="flex justify-between"><dt class="text-on-surface-variant">Produk</dt><dd class="font-semibold text-right">{{ $order->product_name ?? $order->plan }}</dd></div>
                <div class="flex justify-between"><dt class="text-on-surface-variant">Total</dt><dd class="font-bold text-primary-container">Rp {{ number_format($order->amount, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between"><dt class="text-on-surface-variant">Status</dt>
                    <dd>
                        @if($order->status === 'paid')
                            <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[11px] font-bold">LUNAS</span>
                        @elseif($order->status === 'failed')
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[11px] font-bold">GAGAL</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-[11px] font-bold">MENUNGGU</span>
                        @endif
                    </dd>
                </div>
                @if($order->status === 'paid' && $order->license)
                    <div class="flex justify-between gap-2 pt-1 border-t border-outline-variant/60">
                        <dt class="text-on-surface-variant shrink-0">{{ $deliveryViaEmail ? 'Email' : $deliveryLabel }}</dt>
                        <dd class="text-right font-semibold text-[12px]">
                            @if($deliverySent)
                                <span class="text-emerald-700">Terkirim</span>
                            @else
                                <span class="text-amber-800">Menunggu…</span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    @else
        <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-8">
            Pembayaran sudah disubmit. Jika Anda punya kode order dari Midtrans, buka kembali link selesai bayar dari aplikasi pembayaran atau hubungi tim YFD.
        </p>
    @endif

    <div class="flex flex-wrap gap-3 justify-center">
        @if(!$isFtsaOnly && !empty($telegramBotUrl))
            @if(!empty($telegramBotAppUrl))
                <a href="{{ $telegramBotAppUrl }}" class="btn btn-primary">
                    <span class="material-symbols-outlined text-[18px]">smart_toy</span> Buka bot di Telegram
                </a>
            @endif
            <a href="{{ $telegramBotUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                <span class="material-symbols-outlined text-[18px]">open_in_new</span> Buka via t.me
            </a>
        @endif
        <a href="{{ route('company.home') }}" class="btn btn-outline-primary">
            <span class="material-symbols-outlined text-[18px]">home</span> Kembali ke Beranda
        </a>
        <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
            <span class="material-symbols-outlined text-[18px]">chat</span> Chat Tim YFD via WA
        </a>
    </div>
</section>

@endsection
