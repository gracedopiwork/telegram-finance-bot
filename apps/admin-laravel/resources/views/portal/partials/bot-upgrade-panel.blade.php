@if(($isFtsaOnlyPortalUser ?? false) && !($hasBotPortalAccess ?? false))
@php
    $checkoutService = app(\App\Services\PortalCheckoutService::class);
    try {
        $botProduct = $checkoutService->botProduct();
    } catch (\Throwable $e) {
        $botProduct = null;
    }
    $portalEmail = session(\App\Support\PortalSession::EMAIL, '');
    $portalTelegramUserId = (int) session(\App\Support\PortalSession::TELEGRAM_USER_ID, 0);
    $variant = $variant ?? 'block';
    $formId = 'bot-checkout-'.md5($variant.(string) $portalEmail);
    $priceLabel = $botProduct ? \App\Support\RupiahFormat::format($botProduct->effective_price) : null;
    $upgradeStatus = $checkoutService->botUpgradeEligibility($portalEmail, $portalTelegramUserId);
    $canPay = $upgradeStatus['can_pay'];
@endphp

<div class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900" data-portal-snap-root>
    <div class="font-bold">{{ $title ?? 'Upgrade ke YFD First Aid' }}</div>
    <div class="mt-1 leading-relaxed">
        {{ $message ?? 'Pencatatan keuangan harian, mood, impulsivitas, dan Financial Behavioral Dashboard — tanpa keluar dari portal.' }}
    </div>
    @if($priceLabel)
        <div class="mt-2">Total: <strong>{{ $priceLabel }}</strong> · lisensi yang sama dengan FTSA Anda</div>
    @endif
    <form id="{{ $formId }}" class="mt-4" data-portal-snap-form
          data-snap-success-key="bot_unlocked"
          action="{{ route('portal.checkout.bot') }}" method="post" novalidate>
        @csrf
        <button type="submit" data-portal-snap-btn
                @disabled(! $canPay)
                class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-4 py-2 rounded-xl text-sm">
            <span class="material-symbols-outlined text-lg">shopping_cart</span>
            <span data-portal-snap-label>{{ $buttonLabel ?? 'Beli YFD First Aid' }}</span>
        </button>
        <p class="text-xs text-sky-800 mt-2 hidden" data-portal-snap-error></p>
        @unless($canPay)
            <p class="text-xs text-sky-800/80 mt-2">
                @if($upgradeStatus['product_missing'])
                    Produk YFD First Aid belum aktif di katalog. Hubungi tim YFD.
                @elseif(! $upgradeStatus['midtrans_ready'])
                    Pembayaran belum siap — pastikan <code class="text-[11px]">MIDTRANS_CLIENT_KEY</code> dan <code class="text-[11px]">MIDTRANS_SERVER_KEY</code> aktif di server, lalu jalankan <code class="text-[11px]">php artisan config:clear</code>.
                @elseif(! $upgradeStatus['eligible'])
                    Upgrade tidak tersedia untuk lisensi ini (FTSA belum aktif atau bot sudah dibeli).
                @else
                    Pembayaran sementara tidak tersedia. Hubungi tim YFD.
                @endif
            </p>
        @endunless
    </form>
</div>

@include('portal.partials.portal-midtrans-snap')
@endif
