@once
@push('scripts')
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
            if (!data.payment_url) {
                throw new Error('Link pembayaran tidak tersedia.');
            }
            window.location.href = data.payment_url;
        } catch (err) {
            showError(form, err.message || 'Terjadi kesalahan.');
            setLoading(form, false);
        }
    });
})();
</script>
@endpush
@endonce
