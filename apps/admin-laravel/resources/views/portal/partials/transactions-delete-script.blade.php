@once
@push('scripts')
<script>
(() => {
    const csrf = @json(csrf_token());
    const toast = document.getElementById('tx-delete-toast');
    const selectAll = document.getElementById('tx-select-all');
    const selectedDeleteBtn = document.getElementById('tx-delete-selected-btn');
    const monthDeleteBtn = document.getElementById('tx-delete-month-btn');
    let toastTimer = null;

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 2200);
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.tx-select-item:checked'))
            .map((el) => Number(el.value))
            .filter((n) => Number.isInteger(n) && n > 0);
    }

    function refreshSelectedState() {
        if (!selectedDeleteBtn) return;
        selectedDeleteBtn.disabled = getSelectedIds().length === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.tx-select-item').forEach((item) => {
                item.checked = selectAll.checked;
            });
            refreshSelectedState();
        });
    }

    document.querySelectorAll('.tx-select-item').forEach((item) => {
        item.addEventListener('change', () => {
            if (!selectAll) {
                refreshSelectedState();
                return;
            }
            const all = document.querySelectorAll('.tx-select-item');
            const checked = document.querySelectorAll('.tx-select-item:checked');
            selectAll.checked = all.length > 0 && checked.length === all.length;
            refreshSelectedState();
        });
    });

    refreshSelectedState();

    async function deleteRowsByIds(ids, url) {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ transaction_ids: ids }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
            throw new Error(data.message || 'Gagal menghapus transaksi.');
        }
        return data;
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

    if (selectedDeleteBtn) {
        selectedDeleteBtn.addEventListener('click', async () => {
            const ids = getSelectedIds();
            if (!ids.length) return;
            if (!confirm(`Hapus ${ids.length} transaksi terpilih?`)) return;

            selectedDeleteBtn.disabled = true;
            try {
                const data = await deleteRowsByIds(ids, selectedDeleteBtn.dataset.url);
                ids.forEach((id) => {
                    const row = document.querySelector(`[data-tx-row][data-tx-id="${id}"]`);
                    if (row) row.remove();
                });
                if (selectAll) selectAll.checked = false;
                refreshSelectedState();
                if (!document.querySelector('[data-tx-row]')) {
                    window.location.reload();
                    return;
                }
                showToast(data.message || 'Transaksi terpilih dihapus.');
            } catch (err) {
                alert(err.message || 'Gagal menghapus transaksi terpilih.');
                refreshSelectedState();
            }
        });
    }

    if (monthDeleteBtn) {
        monthDeleteBtn.addEventListener('click', async () => {
            const month = monthDeleteBtn.dataset.month || '';
            const monthLabel = monthDeleteBtn.dataset.monthLabel || month;
            if (!month) return;

            if (!confirm(`Hapus SEMUA transaksi pada bulan ${monthLabel}? Aksi ini tidak bisa dibatalkan.`)) return;

            monthDeleteBtn.disabled = true;
            try {
                const res = await fetch(monthDeleteBtn.dataset.url, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ month }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || 'Gagal menghapus transaksi bulanan.');
                }
                showToast(data.message || 'Transaksi bulan ini dihapus.');
                window.location.reload();
            } catch (err) {
                monthDeleteBtn.disabled = false;
                alert(err.message || 'Gagal menghapus transaksi bulan ini.');
            }
        });
    }
})();
</script>
@endpush
@endonce
