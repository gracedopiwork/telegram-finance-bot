@php
    use App\Support\DebtRegister;

    $existingBaseline = $existingBaseline ?? null;
    $blankDebt = [
        'debt_id' => '', 'debt_name' => '', 'debt_type' => '',
        'principal_outstanding' => '', 'monthly_installment_reference' => '',
        'remaining_tenor_months' => '', 'last_updated' => '',
    ];
    $debts = old('snapshot.debt_register', DebtRegister::rowsFor($existingBaseline));
    if (! is_array($debts)) {
        $debts = [];
    }
    while (count($debts) < 2) {
        $debts[] = $blankDebt;
    }
    $maxDebts = DebtRegister::MAX_ROWS;
    $debtTypes = DebtRegister::types();
    // Dihitung server-side dulu supaya angkanya sudah benar sebelum JS jalan.
    $debtTotal = array_sum(array_map(
        fn ($row) => (int) ($row['principal_outstanding'] ?? 0),
        array_filter($debts, 'is_array'),
    ));
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <div class="text-sm font-semibold text-navy-800">Daftar utang (Debt Register)</div>
        <button type="button" id="debt-add-row"
                class="inline-flex items-center gap-1 rounded-lg border border-navy-200 bg-navy-50 text-navy-800 hover:bg-navy-100 px-3 py-1.5 text-xs font-semibold">
            <span class="material-symbols-outlined text-sm">add</span>
            Tambah baris
        </button>
    </div>
    <p class="text-xs text-slate-500 mb-3">
        Rinci utang Anda per item — menggantikan isian "Total utang" tunggal.
        <strong>Sisa pokok diisi manual</strong> dan dikonfirmasi ulang tiap evaluasi 3 bulan;
        angka ini tidak dihitung otomatis dari transaksi cicilan harian, karena cicilan
        mengandung bunga yang porsinya berubah tiap bulan.
    </p>
    <input type="hidden" name="snapshot[debt_register_submitted]" value="1">
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-xs sm:text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-2 py-2 text-left font-medium">Nama utang</th>
                    <th class="px-2 py-2 text-left font-medium">Jenis</th>
                    <th class="px-2 py-2 text-left font-medium">Sisa pokok saat ini (Rp)</th>
                    <th class="px-2 py-2 text-left font-medium">Cicilan/bulan (Rp)</th>
                    <th class="px-2 py-2 text-left font-medium">Tenor sisa (bulan)</th>
                    <th class="px-2 py-2 text-left font-medium">Update terakhir</th>
                    <th class="px-2 py-2 text-right font-medium w-12"></th>
                </tr>
            </thead>
            <tbody id="debt-register-body">
            @foreach($debts as $i => $row)
                <tr class="border-t border-slate-100 debt-register-row" data-debt-row>
                    <td class="px-2 py-2">
                        <input type="hidden" name="snapshot[debt_register][{{ $i }}][debt_id]" value="{{ $row['debt_id'] ?? '' }}">
                        <input type="text" name="snapshot[debt_register][{{ $i }}][debt_name]"
                               value="{{ $row['debt_name'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="KPR BCA">
                    </td>
                    <td class="px-2 py-2">
                        <select name="snapshot[debt_register][{{ $i }}][debt_type]"
                                class="w-full rounded border-slate-300 text-sm">
                            @foreach($debtTypes as $value => $label)
                                <option value="{{ $value }}" @selected(($row['debt_type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[debt_register][{{ $i }}][principal_outstanding]" min="0" step="1000"
                               value="{{ $row['principal_outstanding'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="450000000">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[debt_register][{{ $i }}][monthly_installment_reference]" min="0" step="1000"
                               value="{{ $row['monthly_installment_reference'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="4200000">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[debt_register][{{ $i }}][remaining_tenor_months]" min="0" max="600" step="1"
                               value="{{ $row['remaining_tenor_months'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="96">
                    </td>
                    <td class="px-2 py-2 text-slate-500 whitespace-nowrap">
                        {{ DebtRegister::formatDate($row['last_updated'] ?? null) }}
                    </td>
                    <td class="px-2 py-2 text-right align-middle">
                        <button type="button" class="debt-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1"
                                aria-label="Hapus baris" title="Hapus baris">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="bg-slate-50 border-t-2 border-slate-300">
                <tr>
                    {{-- Sengaja <td>, bukan input: tanpa name= baris ini tidak ikut
                         terkirim dan tidak bisa diisi user. --}}
                    <td class="px-2 py-2 font-semibold text-navy-800" colspan="2">Total sisa pokok</td>
                    <td class="px-2 py-2 font-bold text-navy-800" id="debt-total-principal"
                        data-debt-total>{{ number_format($debtTotal, 0, ',', '.') }}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="text-[11px] text-slate-400 mt-2">
        Contoh: KPR BCA · KPR · 450.000.000 · 4.200.000 · 96 · Kartu kredit boleh dikosongkan tenornya.
        Bisa isi sampai {{ $maxDebts }} baris. "Update terakhir" terisi otomatis saat Anda mengubah sisa pokok atau cicilan.
    </p>
</div>

@once
@push('scripts')
<script>
(() => {
    const body = document.getElementById('debt-register-body');
    const addBtn = document.getElementById('debt-add-row');
    if (!body || !addBtn) return;

    const maxRows = {{ $maxDebts }};
    const typeOptions = @json($debtTypes);

    const totalCell = document.getElementById('debt-total-principal');
    const rupiah = new Intl.NumberFormat('id-ID');

    function recalcTotal() {
        if (!totalCell) return;
        let total = 0;
        body.querySelectorAll('input[name*="[principal_outstanding]"]').forEach((input) => {
            const value = parseInt(input.value, 10);
            if (!Number.isNaN(value)) total += value;
        });
        totalCell.textContent = rupiah.format(total);
    }

    // Delegated: baris baru ikut terpantau tanpa perlu bind ulang per input.
    body.addEventListener('input', (event) => {
        if (event.target.matches('input[name*="[principal_outstanding]"]')) recalcTotal();
    });

    function reindexRows() {
        Array.from(body.querySelectorAll('[data-debt-row]')).forEach((row, i) => {
            row.querySelectorAll('input[name], select[name]').forEach((input) => {
                // Nama input berbentuk snapshot[debt_register][N][field] — pola harus
                // menyertakan "]" sebelum indeks, kalau tidak regex tidak pernah cocok
                // dan indeks bentrok setelah hapus+tambah (satu baris hilang saat simpan).
                input.name = input.name.replace(/debt_register]\[\d+]/, 'debt_register][' + i + ']');
            });
        });
        const count = body.querySelectorAll('[data-debt-row]').length;
        addBtn.disabled = count >= maxRows;
        addBtn.classList.toggle('opacity-50', count >= maxRows);
        recalcTotal();
    }

    function bindRemove(btn) {
        btn.addEventListener('click', () => {
            const rows = body.querySelectorAll('[data-debt-row]');
            if (rows.length <= 1) {
                const row = btn.closest('[data-debt-row]');
                if (row) {
                    row.querySelectorAll('input').forEach((el) => { el.value = ''; });
                    row.querySelectorAll('select').forEach((el) => { el.selectedIndex = 0; });
                }
                return;
            }
            const row = btn.closest('[data-debt-row]');
            if (row) row.remove();
            reindexRows();
        });
    }

    body.querySelectorAll('.debt-remove-row').forEach(bindRemove);

    addBtn.addEventListener('click', () => {
        const count = body.querySelectorAll('[data-debt-row]').length;
        if (count >= maxRows) return;

        const i = count;
        const options = Object.entries(typeOptions)
            .map(([v, l]) => '<option value="' + v + '">' + l + '</option>').join('');
        const tr = document.createElement('tr');
        tr.className = 'border-t border-slate-100 debt-register-row';
        tr.setAttribute('data-debt-row', '');
        tr.innerHTML =
            '<td class="px-2 py-2"><input type="hidden" name="snapshot[debt_register][' + i + '][debt_id]" value=""><input type="text" name="snapshot[debt_register][' + i + '][debt_name]" class="w-full rounded border-slate-300 text-sm" placeholder="KPR BCA"></td>' +
            '<td class="px-2 py-2"><select name="snapshot[debt_register][' + i + '][debt_type]" class="w-full rounded border-slate-300 text-sm">' + options + '</select></td>' +
            '<td class="px-2 py-2"><input type="number" name="snapshot[debt_register][' + i + '][principal_outstanding]" min="0" step="1000" class="w-full rounded border-slate-300 text-sm" placeholder="450000000"></td>' +
            '<td class="px-2 py-2"><input type="number" name="snapshot[debt_register][' + i + '][monthly_installment_reference]" min="0" step="1000" class="w-full rounded border-slate-300 text-sm" placeholder="4200000"></td>' +
            '<td class="px-2 py-2"><input type="number" name="snapshot[debt_register][' + i + '][remaining_tenor_months]" min="0" max="600" step="1" class="w-full rounded border-slate-300 text-sm" placeholder="96"></td>' +
            '<td class="px-2 py-2 text-slate-500 whitespace-nowrap">&mdash;</td>' +
            '<td class="px-2 py-2 text-right align-middle"><button type="button" class="debt-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1" aria-label="Hapus baris" title="Hapus baris"><span class="material-symbols-outlined text-base">close</span></button></td>';
        body.appendChild(tr);
        bindRemove(tr.querySelector('.debt-remove-row'));
        reindexRows();
    });

    reindexRows();
})();
</script>
@endpush
@endonce
