@once
@push('scripts')
<script>
(() => {
    const csrf = @json(csrf_token());
    const toast = document.getElementById('tx-delete-toast');
    let toastTimer = null;

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 2200);
    }

    document.querySelectorAll('[data-delete-tx]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Hapus transaksi ini?')) return;

            const row = btn.closest('[data-tx-row]');
            if (!row) return;

            btn.disabled = true;
            try {
                const res = await fetch(btn.dataset.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || 'Gagal menghapus transaksi.');
                }

                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    if (!document.querySelector('[data-tx-row]')) {
                        window.location.reload();
                    }
                }, 150);

                showToast(data.message || 'Transaksi dihapus.');
            } catch (err) {
                btn.disabled = false;
                alert(err.message || 'Gagal menghapus transaksi.');
            }
        });
    });
})();
</script>
@endpush
@endonce
