@once
@push('scripts')
<script>
(() => {
    const csrf = @json(csrf_token());
    const fmt = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
    let income = {{ (int) ($summary['income'] ?? 0) }};
    let expense = {{ (int) ($summary['expense'] ?? 0) }};
    let savingInvestment = {{ (int) ($summary['saving_investment'] ?? 0) }};
    let count = {{ (int) ($summary['transaction_count'] ?? 0) }};
    const toast = document.getElementById('tx-delete-toast');
    let toastTimer = null;

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 2200);
    }

    function updateStats() {
        const savingRate = income > 0 ? Math.round((savingInvestment / income) * 1000) / 10 : 0;
        const countEl = document.getElementById('tx-stat-count');
        const incomeEl = document.getElementById('tx-stat-income');
        const expenseEl = document.getElementById('tx-stat-expense');
        const savingAmtEl = document.getElementById('tx-stat-saving-amt');
        const savingEl = document.getElementById('tx-stat-saving');
        if (countEl) countEl.textContent = String(count);
        if (incomeEl) incomeEl.textContent = fmt(income);
        if (expenseEl) expenseEl.textContent = fmt(expense);
        if (savingAmtEl) savingAmtEl.textContent = fmt(savingInvestment);
        if (savingEl) savingEl.textContent = savingRate + '%';
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

                const type = row.dataset.txType;
                const amount = parseInt(row.dataset.txAmount || '0', 10);
                if (type === 'Pemasukan') income = Math.max(0, income - amount);
                if (type === 'Pengeluaran') expense = Math.max(0, expense - amount);
                if (type === 'Saving/Investment') savingInvestment = Math.max(0, savingInvestment - amount);
                count = Math.max(0, count - 1);
                updateStats();

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
