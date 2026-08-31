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

    // --- Sort & filter tabel transaksi ---
    const tbody = document.getElementById('tx-table-body');
    const filterCategory = document.getElementById('tx-filter-category');
    const filterBucket = document.getElementById('tx-filter-bucket');
    const filterCount = document.getElementById('tx-filter-count');
    const filterTotal = document.getElementById('tx-filter-total');
    const filterTotalDetail = document.getElementById('tx-filter-total-detail');
    const headerNominalTotal = document.getElementById('tx-nominal-header-total');
    const footNominal = document.getElementById('tx-foot-nominal');
    const footDetail = document.getElementById('tx-foot-detail');
    let sortKey = null;
    let sortDir = 1; // 1 asc, -1 desc

    function formatRp(n) {
        return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');
    }

    function visibleRows() {
        if (!tbody) return [];
        return Array.from(tbody.querySelectorAll('[data-tx-row]')).filter((r) => r.style.display !== 'none');
    }

    function summarizeVisible() {
        const rows = visibleRows();
        let total = 0;
        const byType = {};
        rows.forEach((row) => {
            const amount = Number(row.dataset.txAmount || 0);
            const type = (row.dataset.txType || 'Lainnya').trim() || 'Lainnya';
            total += amount;
            byType[type] = (byType[type] || 0) + amount;
        });
        return { rows: rows.length, total, byType };
    }

    function updateFilterCount() {
        if (!tbody) return;
        const all = tbody.querySelectorAll('[data-tx-row]').length;
        const summary = summarizeVisible();
        const shown = summary.rows;

        if (filterCount) {
            filterCount.textContent = shown === all ? `${all} baris` : `${shown} dari ${all} baris`;
        }

        const totalLabel = formatRp(summary.total);
        if (filterTotal) {
            filterTotal.textContent = shown ? `Total Nominal: ${totalLabel}` : 'Total Nominal: —';
        }
        if (headerNominalTotal) {
            headerNominalTotal.textContent = shown ? totalLabel : '—';
        }
        if (footNominal) {
            footNominal.textContent = shown ? totalLabel : '—';
        }

        const typeParts = Object.entries(summary.byType)
            .sort((a, b) => b[1] - a[1])
            .map(([type, amount]) => `${type} ${formatRp(amount)}`);
        const detail = typeParts.length > 1 ? typeParts.join(' · ') : '';
        if (filterTotalDetail) filterTotalDetail.textContent = detail;
        if (footDetail) footDetail.textContent = detail;
    }

    function applyFilters() {
        if (!tbody) return;
        const cat = (filterCategory && filterCategory.value) || '';
        const bucket = (filterBucket && filterBucket.value) || '';
        tbody.querySelectorAll('[data-tx-row]').forEach((row) => {
            const matchCat = !cat || (row.dataset.txCategory || '') === cat;
            const matchBucket = !bucket || (row.dataset.txBucket || '') === bucket;
            row.style.display = matchCat && matchBucket ? '' : 'none';
        });
        updateFilterCount();
    }

    function sortValue(row, key, type) {
        if (key === 'date') return row.dataset.txDate || '';
        if (key === 'type') return row.dataset.txType || '';
        if (key === 'category') return row.dataset.txCategory || '';
        if (key === 'bucket') return row.dataset.txBucket || '';
        if (key === 'amount') return Number(row.dataset.txAmount || 0);
        return '';
    }

    function applySort(key, type) {
        if (!tbody) return;
        if (sortKey === key) {
            sortDir *= -1;
        } else {
            sortKey = key;
            sortDir = 1;
        }

        document.querySelectorAll('.tx-sort').forEach((btn) => {
            const icon = btn.querySelector('.tx-sort-icon');
            if (!icon) return;
            if (btn.dataset.sort === key) {
                icon.textContent = sortDir === 1 ? 'arrow_upward' : 'arrow_downward';
                icon.classList.remove('opacity-40');
            } else {
                icon.textContent = 'unfold_more';
                icon.classList.add('opacity-40');
            }
        });

        const rows = Array.from(tbody.querySelectorAll('[data-tx-row]'));
        rows.sort((a, b) => {
            const va = sortValue(a, key, type);
            const vb = sortValue(b, key, type);
            if (type === 'number') {
                return (va - vb) * sortDir;
            }
            return String(va).localeCompare(String(vb), 'id', { sensitivity: 'base' }) * sortDir;
        });
        rows.forEach((row) => tbody.appendChild(row));
    }

    document.querySelectorAll('.tx-sort').forEach((btn) => {
        btn.addEventListener('click', () => {
            applySort(btn.dataset.sort, btn.dataset.type || 'text');
        });
    });

    if (filterCategory) filterCategory.addEventListener('change', applyFilters);
    if (filterBucket) filterBucket.addEventListener('change', applyFilters);
    updateFilterCount();

    // Refresh total setelah hapus baris (tanpa reload penuh).
    if (tbody) {
        const mo = new MutationObserver(() => updateFilterCount());
        mo.observe(tbody, { childList: true, subtree: false });
    }
})();
</script>
@endpush
@endonce
