@if(!($ftsaUnlocked ?? false))
@php
    $checkoutService = app(\App\Services\PortalCheckoutService::class);
    $midtrans = app(\App\Services\MidtransService::class);
    try {
        $ftsaProduct = $checkoutService->product();
    } catch (\Throwable $e) {
        $ftsaProduct = null;
    }
    $portalEmail = session(\App\Support\PortalSession::EMAIL, '');
    $variant = $variant ?? 'banner';
    $formId = 'ftsa-checkout-'.md5($variant.(string) $portalEmail);
    $priceLabel = $ftsaProduct ? \App\Support\RupiahFormat::format($ftsaProduct->effective_price) : null;
    $canPay = $ftsaProduct !== null && $midtrans->clientKey() !== '';
@endphp

@if($variant === 'inline')
    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50/80 p-4" data-ftsa-checkout-root>
        @if($priceLabel)
            <div class="text-xs text-amber-800 mb-3">Total: <strong>{{ $priceLabel }}</strong> · akses 12 bulan evaluasi</div>
        @endif
        <form id="{{ $formId }}" data-ftsa-checkout-form
              action="{{ route('portal.checkout.ftsa') }}" method="post" novalidate>
            @csrf
            <button type="submit" data-ftsa-pay-btn
                    @disabled(! $canPay)
                    class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 disabled:opacity-50 disabled:cursor-not-allowed text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">lock_open</span>
                <span data-ftsa-pay-label>Unlock FTSA Premium</span>
            </button>
            <p class="text-xs text-amber-800/80 mt-2 hidden" data-ftsa-checkout-error></p>
            @unless($canPay)
                <p class="text-xs text-amber-800/80 mt-2">Pembayaran sementara tidak tersedia. Hubungi tim YFD.</p>
            @endunless
        </form>
    </div>
@elseif($variant === 'block')
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-left" data-ftsa-checkout-root>
        <div class="text-sm text-amber-900 font-bold">FTSA Premium tersedia di dalam dashboard</div>
        <div class="text-sm text-amber-800 mt-1">Bisa dibeli sekarang untuk membuka FTSA 1-32 selama <strong>12 bulan evaluasi</strong>.</div>
        @if($priceLabel)
            <div class="text-sm text-amber-900 mt-2">Total pembayaran: <strong>{{ $priceLabel }}</strong></div>
        @endif
        <form id="{{ $formId }}" class="mt-4" data-ftsa-checkout-form
              action="{{ route('portal.checkout.ftsa') }}" method="post" novalidate>
            @csrf
            <button type="submit" data-ftsa-pay-btn
                    @disabled(! $canPay)
                    class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 disabled:opacity-50 disabled:cursor-not-allowed text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">shopping_cart</span>
                <span data-ftsa-pay-label>Beli FTSA Premium</span>
            </button>
            <p class="text-xs text-amber-800 mt-2 hidden" data-ftsa-checkout-error></p>
        </form>
    </div>
@else
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4" data-ftsa-checkout-root>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="text-sm text-amber-900 min-w-0 flex-1">
                <div class="font-bold">{{ $title ?? 'FTSA Premium belum aktif' }}</div>
                <div class="mt-0.5">{{ $message ?? 'Unlock kuesioner FTSA 1–32 dan rekomendasi personal selama 12 bulan evaluasi.' }}</div>
                @if($priceLabel)
                    <div class="mt-1 text-xs text-amber-800">{{ $priceLabel }}</div>
                @endif
            </div>
            <form id="{{ $formId }}" data-ftsa-checkout-form
                  action="{{ route('portal.checkout.ftsa') }}" method="post" novalidate>
                @csrf
                <button type="submit" data-ftsa-pay-btn
                        @disabled(! $canPay)
                        class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 disabled:opacity-50 disabled:cursor-not-allowed text-navy-900 font-bold px-4 py-2 rounded-xl text-sm whitespace-nowrap">
                    <span class="material-symbols-outlined text-lg">lock_open</span>
                    <span data-ftsa-pay-label>Beli FTSA Premium</span>
                </button>
            </form>
        </div>
        <p class="text-xs text-amber-800 mt-2 hidden" data-ftsa-checkout-error></p>
    </div>
@endif

@once
@push('scripts')
@php
    $snapJsUrl = app(\App\Services\MidtransService::class)->snapJsUrl();
    $snapClientKey = app(\App\Services\MidtransService::class)->clientKey();
@endphp
@if($snapClientKey !== '')
<script src="{{ $snapJsUrl }}" data-client-key="{{ $snapClientKey }}"></script>
<script>
(function () {
    function checkoutRoot(form) {
        return form.closest('[data-ftsa-checkout-root]');
    }

    function showError(form, message) {
        var root = checkoutRoot(form);
        var el = root ? root.querySelector('[data-ftsa-checkout-error]') : null;
        if (!el) return;
        el.textContent = message || 'Gagal memproses pembayaran.';
        el.classList.remove('hidden');
    }

    function clearError(form) {
        var root = checkoutRoot(form);
        var el = root ? root.querySelector('[data-ftsa-checkout-error]') : null;
        if (!el) return;
        el.textContent = '';
        el.classList.add('hidden');
    }

    function setLoading(form, loading) {
        var btn = form.querySelector('[data-ftsa-pay-btn]');
        var label = form.querySelector('[data-ftsa-pay-label]');
        if (!btn) return;
        btn.disabled = loading;
        if (label) {
            label.textContent = loading ? 'Memproses...' : (btn.dataset.defaultLabel || label.textContent);
        }
    }

    async function pollStatus(url) {
        for (var i = 0; i < 24; i++) {
            var res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            var data = await res.json();
            if (data.status === 'paid' || data.ftsa_unlocked) {
                return data;
            }
            if (data.status === 'failed') {
                throw new Error('Pembayaran gagal atau dibatalkan.');
            }
            await new Promise(function (r) { setTimeout(r, 2000); });
        }
        throw new Error('Konfirmasi pembayaran belum masuk. Refresh halaman dalam beberapa menit.');
    }

    function openSnap(token, statusUrl) {
        return new Promise(function (resolve, reject) {
            var finished = false;
            if (typeof window.snap === 'undefined') {
                reject(new Error('Midtrans Snap belum dimuat. Refresh halaman lalu coba lagi.'));
                return;
            }
            window.snap.pay(token, {
                onSuccess: function () { finished = true; resolve('success'); },
                onPending: function () { finished = true; resolve('pending'); },
                onError: function () { finished = true; reject(new Error('Pembayaran gagal.')); },
                onClose: function () {
                    if (!finished) {
                        reject(new Error('Pembayaran dibatalkan.'));
                    }
                },
            });
        }).then(function () { return pollStatus(statusUrl); });
    }

    document.addEventListener('submit', async function (event) {
        var form = event.target;
        if (!form.matches('[data-ftsa-checkout-form]')) return;
        event.preventDefault();
        clearError(form);

        var btn = form.querySelector('[data-ftsa-pay-btn]');
        if (btn && !btn.dataset.defaultLabel) {
            var label = form.querySelector('[data-ftsa-pay-label]');
            btn.dataset.defaultLabel = label ? label.textContent : 'Bayar';
        }

        setLoading(form, true);
        try {
            var body = new FormData(form);
            var res = await fetch(form.action, {
                method: 'POST',
                body: body,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            var data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Gagal membuat pembayaran.');
            }
            await openSnap(data.snap_token, data.status_url);
            window.location.reload();
        } catch (err) {
            showError(form, err.message || 'Terjadi kesalahan.');
            setLoading(form, false);
        }
    });
})();
</script>
@endif
@endpush
@endonce
@endif
