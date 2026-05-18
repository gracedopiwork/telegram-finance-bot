@extends('Companyprofile.layouts.main')

@section('title', 'Pembayaran Diproses — YFD')

@php
    $sheetPoll = (int) request()->query('sheet_poll', 0);
    $sheetHref = null;
    if ($order) {
        if (! empty($order->spreadsheet_url)) {
            $sheetHref = $order->spreadsheet_url;
        } elseif (! empty($order->spreadsheet_id)) {
            $sheetHref = 'https://docs.google.com/spreadsheets/d/' . $order->spreadsheet_id . '/edit';
        }
    }
    $sheetOk = $sheetHref !== null;
    $sheetFailedAfterJob = $order
        && $order->status === 'paid'
        && $order->license
        && ! $sheetOk
        && $order->purchase_delivery_sent_at !== null;
    $sheetWaitingForJob = $order
        && $order->status === 'paid'
        && $order->license
        && ! $sheetOk
        && $order->purchase_delivery_sent_at === null;
    $sheetPollMax = 45;
    $sheetPollInterval = 8;
@endphp

@push('head')
    @if($order && $order->status === 'pending')
        {{-- Midtrans webhook bisa beberapa detik setelah redirect; muat ulang agar kode lisensi muncul setelah lunas --}}
        <meta http-equiv="refresh" content="12">
    @elseif($sheetWaitingForJob && $sheetPoll < $sheetPollMax)
        {{-- Job DeliverPaidOrderJob async: polling sampai spreadsheet_id/url terisi atau batas waktu --}}
        <meta http-equiv="refresh" content="{{ $sheetPollInterval }};url={{ request()->fullUrlWithQuery(['sheet_poll' => $sheetPoll + 1]) }}">
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
            <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-4">
                Pembayaran <strong>lunas</strong>. Simpan kode lisensi di bawah ini — kode ini harus sama persis saat Anda
                <strong>/activate</strong> di bot Telegram. Email ringkasan tetap dikirim jika pengiriman email aktif.
            </p>

            <div class="bg-primary/5 border-2 border-primary/20 rounded-2xl p-6 max-w-lg mx-auto text-left mb-6">
                <p class="text-[11px] font-bold uppercase tracking-wider text-primary mb-2">Kode lisensi bot</p>
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

            <div class="max-w-lg mx-auto text-left mb-6 border-2 rounded-2xl p-5
                @if($sheetOk) border-emerald-200 bg-emerald-50/80
                @elseif($sheetFailedAfterJob) border-red-200 bg-red-50/70
                @else border-amber-200 bg-amber-50/70 @endif">
                <p class="text-[11px] font-bold uppercase tracking-wider mb-2
                    @if($sheetOk) text-emerald-800
                    @elseif($sheetFailedAfterJob) text-red-800
                    @else text-amber-900 @endif">
                    Status Google Sheet
                </p>
                @if($sheetOk)
                    <p class="text-body-md text-on-surface-variant mb-2">
                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-800">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span> Berhasil
                        </span>
                        — spreadsheet untuk order ini sudah dibuat.
                    </p>
                    <p class="text-[13px] mb-2">
                        <a href="{{ $sheetHref }}" target="_blank" rel="noopener" class="text-primary font-semibold underline break-all">{{ $sheetHref }}</a>
                    </p>
                    <p class="text-[12px] text-on-surface-variant mb-0 leading-relaxed">
                        Buka link di atas dengan akun Google <strong>{{ $order->email }}</strong> (email yang Anda isi saat checkout).
                        Jika browser memakai Gmail lain, klik foto profil → <strong>Ganti akun</strong>.
                    </p>
                @elseif($sheetFailedAfterJob)
                    <p class="text-body-md text-on-surface-variant mb-2">
                        <span class="inline-flex items-center gap-1 font-semibold text-red-800">
                            <span class="material-symbols-outlined text-[20px]">error</span> Tidak terbuat otomatis
                        </span>
                        — proses pengiriman order sudah selesai di server, tetapi tidak ada ID/link spreadsheet di database.
                    </p>
                    <p class="text-[12px] text-on-surface-variant leading-relaxed mb-0">
                        Konfigurasi sudah benar di server tetapi order ini gagal saat pembayaran pertama — minta admin jalankan
                        <code class="text-[11px] bg-white/80 px-1 rounded">php artisan google:sheet-setup --provision={{ $order->order_code }}</code>
                        atau tombol &quot;Salin ulang&quot; di panel admin. Tanpa itu halaman ini tidak berubah meskipun <code class="text-[11px] bg-white/80 px-1 rounded">google:sheet-setup</code> tanpa <code class="text-[11px] bg-white/80 px-1 rounded">--provision</code> sudah OK.
                    </p>
                @elseif($sheetWaitingForJob && $sheetPoll < $sheetPollMax)
                    <p class="text-body-md text-on-surface-variant mb-2">
                        <span class="inline-flex items-center gap-1 font-semibold text-amber-900">
                            <span class="material-symbols-outlined text-[20px] animate-pulse">hourglass_top</span> Menunggu
                        </span>
                        — sistem sedang menyalin template Google Sheet (antrian). Halaman ini memuat ulang otomatis setiap {{ $sheetPollInterval }} detik
                        ({{ $sheetPoll + 1 }}/{{ $sheetPollMax }}).
                    </p>
                    <p class="text-[12px] text-on-surface-variant mb-0">
                        Jika lama tidak berubah, pastikan worker antrian jalan di VPS.
                    </p>
                @else
                    <p class="text-body-md text-on-surface-variant mb-2">
                        <span class="inline-flex items-center gap-1 font-semibold text-amber-900">
                            <span class="material-symbols-outlined text-[20px]">schedule</span> Batas polling
                        </span>
                        — setelah beberapa kali refresh, spreadsheet masih belum muncul.
                    </p>
                    <p class="text-[12px] text-on-surface-variant mb-0">
                        Muat ulang manual (F5) atau cek antrian worker, konfigurasi Google, dan log Laravel. Anda tetap bisa memakai kode lisensi di atas untuk <strong>/activate</strong> di bot.
                    </p>
                @endif
            </div>
        @else
            <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-8">
                @if($order->status === 'pending')
                    Status pembayaran dikonfirmasi Midtrans dalam beberapa menit. Halaman ini <strong>otomatis dimuat ulang</strong> setiap 12 detik
                    sampai status berubah. Setelah <strong>lunas</strong>, <strong>kode lisensi</strong> akan tampil di sini
                    (tidak hanya lewat email). Anda juga bisa menyegarkan manual (F5).
                @else
                    Setelah <strong>lunas</strong>, kode lisensi dan tautan Google Sheet tampil di halaman ini
                    serta dikirim ke email <strong>{{ $order->email }}</strong> bila pengiriman email aktif.
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
                        <dt class="text-on-surface-variant shrink-0">Google Sheet</dt>
                        <dd class="text-right font-semibold text-[12px]">
                            @if($sheetOk)
                                <span class="text-emerald-700">Siap</span>
                            @elseif($sheetFailedAfterJob)
                                <span class="text-red-700">Gagal / tidak ada</span>
                            @elseif($sheetWaitingForJob && $sheetPoll < $sheetPollMax)
                                <span class="text-amber-800">Memproses…</span>
                            @else
                                <span class="text-amber-800">Belum terlihat</span>
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
        @if(!empty($telegramBotUrl))
            <a href="{{ $telegramBotUrl }}" target="_blank" rel="noopener" class="btn btn-primary">
                <span class="material-symbols-outlined text-[18px]">smart_toy</span> Buka bot Telegram
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
