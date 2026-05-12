@extends('Companyprofile.layouts.main')

@section('title', 'Pembayaran Diproses — YFD')

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
        <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-8">
            Status pembayaran dikonfirmasi Midtrans dalam beberapa menit. Setelah <strong>lunas</strong>, ke email
            <strong>{{ $order->email }}</strong> akan dikirim: <strong>tautan bot Telegram</strong>, <strong>kode lisensi</strong>, dan <strong>link Google Sheet</strong>.
        </p>

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
            </dl>
        </div>
    @else
        <p class="text-body-md text-on-surface-variant max-w-xl mx-auto mb-8">
            Pembayaran sudah disubmit. Cek email Anda untuk update status.
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
