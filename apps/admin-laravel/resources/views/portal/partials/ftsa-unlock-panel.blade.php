@if(!($ftsaUnlocked ?? false))
@php
    $checkoutService = app(\App\Services\PortalCheckoutService::class);
    $pivot = app(\App\Services\PivotService::class);
    try {
        $ftsaProduct = $checkoutService->product();
    } catch (\Throwable $e) {
        $ftsaProduct = null;
    }
    $portalEmail = session(\App\Support\PortalSession::EMAIL, '');
    $variant = $variant ?? 'banner';
    $formId = 'ftsa-checkout-'.md5($variant.(string) $portalEmail);
    $priceLabel = $ftsaProduct ? \App\Support\RupiahFormat::format($ftsaProduct->effective_price) : null;
    $canPay = $ftsaProduct !== null && $pivot->isReady();
@endphp

@if($variant === 'embedded')
    <div data-portal-snap-root>
    <form id="{{ $formId }}" data-portal-snap-form
          data-snap-success-key="ftsa_unlocked"
          action="{{ route('portal.checkout.ftsa') }}" method="post" novalidate>
        @csrf
        <button type="submit" data-portal-snap-btn
                @disabled(! $canPay)
                class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 disabled:opacity-50 disabled:cursor-not-allowed text-navy-900 font-bold px-4 py-2.5 rounded-xl text-sm">
            <span data-portal-snap-label>Beli FTSA Premium</span>
        </button>
        @if($priceLabel)
            <p class="text-xs text-slate-500 mt-2">{{ $priceLabel }} · akses 12 bulan evaluasi</p>
        @endif
        <p class="text-xs text-rose-600 mt-2 hidden" data-portal-snap-error></p>
        @unless($canPay)
            <p class="text-xs text-slate-500 mt-2">Pembayaran sementara tidak tersedia. Hubungi tim YFD.</p>
        @endunless
    </form>
    </div>
@elseif($variant === 'inline')
    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50/80 p-4" data-portal-snap-root>
        @if($priceLabel)
            <div class="text-xs text-amber-800 mb-3">Total: <strong>{{ $priceLabel }}</strong> · akses 12 bulan evaluasi</div>
        @endif
        <form id="{{ $formId }}" data-portal-snap-form
              data-snap-success-key="ftsa_unlocked"
              action="{{ route('portal.checkout.ftsa') }}" method="post" novalidate>
            @csrf
            <button type="submit" data-portal-snap-btn
                    @disabled(! $canPay)
                    class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 disabled:opacity-50 disabled:cursor-not-allowed text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">lock_open</span>
                <span data-portal-snap-label>Unlock FTSA Premium</span>
            </button>
            <p class="text-xs text-amber-800/80 mt-2 hidden" data-portal-snap-error></p>
            @unless($canPay)
                <p class="text-xs text-amber-800/80 mt-2">Pembayaran sementara tidak tersedia. Hubungi tim YFD.</p>
            @endunless
        </form>
    </div>
@elseif($variant === 'block')
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-left" data-portal-snap-root>
        <div class="text-sm text-amber-900 font-bold">FTSA Premium tersedia di dalam dashboard</div>
        <div class="text-sm text-amber-800 mt-1">Bisa dibeli sekarang untuk membuka FTSA 1-32 selama <strong>12 bulan evaluasi</strong>.</div>
        @if($priceLabel)
            <div class="text-sm text-amber-900 mt-2">Total pembayaran: <strong>{{ $priceLabel }}</strong></div>
        @endif
        <form id="{{ $formId }}" class="mt-4" data-portal-snap-form
              data-snap-success-key="ftsa_unlocked"
              action="{{ route('portal.checkout.ftsa') }}" method="post" novalidate>
            @csrf
            <button type="submit" data-portal-snap-btn
                    @disabled(! $canPay)
                    class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 disabled:opacity-50 disabled:cursor-not-allowed text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">shopping_cart</span>
                <span data-portal-snap-label>Beli FTSA Premium</span>
            </button>
            <p class="text-xs text-amber-800 mt-2 hidden" data-portal-snap-error></p>
        </form>
    </div>
@else
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4" data-portal-snap-root>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="text-sm text-amber-900 min-w-0 flex-1">
                <div class="font-bold">{{ $title ?? 'FTSA Premium belum aktif' }}</div>
                <div class="mt-0.5">{{ $message ?? 'Unlock kuesioner FTSA 1–32 dan rekomendasi personal selama 12 bulan evaluasi.' }}</div>
                @if($priceLabel)
                    <div class="mt-1 text-xs text-amber-800">{{ $priceLabel }}</div>
                @endif
            </div>
            <form id="{{ $formId }}" data-portal-snap-form
                  data-snap-success-key="ftsa_unlocked"
                  action="{{ route('portal.checkout.ftsa') }}" method="post" novalidate>
                @csrf
                <button type="submit" data-portal-snap-btn
                        @disabled(! $canPay)
                        class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 disabled:opacity-50 disabled:cursor-not-allowed text-navy-900 font-bold px-4 py-2 rounded-xl text-sm whitespace-nowrap">
                    <span class="material-symbols-outlined text-lg">lock_open</span>
                    <span data-portal-snap-label>Beli FTSA Premium</span>
                </button>
            </form>
        </div>
        <p class="text-xs text-amber-800 mt-2 hidden" data-portal-snap-error></p>
    </div>
@endif

@include('portal.partials.portal-midtrans-snap')
@endif
