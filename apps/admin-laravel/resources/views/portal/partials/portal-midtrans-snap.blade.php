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
        return form.closest('[data-portal-snap-root]');
    }

    function showError(form, message) {
        var root = checkoutRoot(form);
        var el = root ? root.querySelector('[data-portal-snap-error]') : null;
        if (!el) return;
        el.textContent = message || 'Gagal memproses pembayaran.';
        el.classList.remove('hidden');
    }

    function clearError(form) {
        var root = checkoutRoot(form);
        var el = root ? root.querySelector('[data-portal-snap-error]') : null;
        if (!el) return;
        el.textContent = '';
        el.classList.add('hidden');
    }

    function setLoading(form, loading) {
        var btn = form.querySelector('[data-portal-snap-btn]');
        var label = form.querySelector('[data-portal-snap-label]');
        if (!btn) return;
        btn.disabled = loading;
        if (label) {
            label.textContent = loading ? 'Memproses...' : (btn.dataset.defaultLabel || label.textContent);
        }
    }

    async function pollStatus(url, successKey) {
        for (var i = 0; i < 24; i++) {
            var res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            var data = await res.json();
            if (data.status === 'paid' || (successKey && data[successKey])) {
                return data;
            }
            if (data.status === 'failed') {
                throw new Error('Pembayaran gagal atau dibatalkan.');
            }
            await new Promise(function (r) { setTimeout(r, 2000); });
        }
        throw new Error('Konfirmasi pembayaran belum masuk. Refresh halaman dalam beberapa menit.');
    }

    function openSnap(token, statusUrl, successKey) {
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
        }).then(function () { return pollStatus(statusUrl, successKey); });
    }

    document.addEventListener('submit', async function (event) {
        var form = event.target;
        if (!form.matches('[data-portal-snap-form]')) return;
        event.preventDefault();
        clearError(form);

        var btn = form.querySelector('[data-portal-snap-btn]');
        if (btn && !btn.dataset.defaultLabel) {
            var label = form.querySelector('[data-portal-snap-label]');
            btn.dataset.defaultLabel = label ? label.textContent : 'Bayar';
        }

        var successKey = form.dataset.snapSuccessKey || '';

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
            await openSnap(data.snap_token, data.status_url, successKey);
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
