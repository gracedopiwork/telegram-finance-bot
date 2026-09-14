@php
    use App\Support\InvestmentRegister;

    $existingBaseline = $existingBaseline ?? null;
    $blankInvestment = [
        'investment_id' => '', 'investment_type' => '', 'purpose' => '',
        'start_year' => '', 'target_years' => '', 'target_amount' => '',
        'realized_principal' => '', 'realized_actual' => '', 'last_updated' => '',
    ];
    $investments = old('snapshot.investment_register', $existingBaseline?->investment_register ?? []);
    if (! is_array($investments)) {
        $investments = [];
    }
    while (count($investments) < 2) {
        $investments[] = $blankInvestment;
    }
    $maxInvestments = InvestmentRegister::MAX_ROWS;
    $investmentTypes = InvestmentRegister::types();

    // Dihitung server-side dulu supaya angkanya sudah benar sebelum JS jalan.
    $investmentRowsForTotal = array_filter($investments, 'is_array');
    $investmentTotalPrincipal = array_sum(array_map(
        fn ($row) => (int) ($row['realized_principal'] ?? 0),
        $investmentRowsForTotal,
    ));
    $investmentTotalActual = array_sum(array_map(
        fn ($row) => (int) ($row['realized_actual'] ?? 0),
        $investmentRowsForTotal,
    ));
    $investmentTotalYield = InvestmentRegister::yieldPercent(
        $investmentTotalPrincipal ?: null,
        $investmentTotalPrincipal ? $investmentTotalActual : null,
    );

    $yieldClass = fn (?float $y) => $y === null
        ? 'text-slate-500'
        : ($y < 0 ? 'text-rose-600' : 'text-emerald-700');
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <div class="text-sm font-semibold text-navy-800">Daftar investasi</div>
        <button type="button" id="investment-add-row"
                class="inline-flex items-center gap-1 rounded-lg border border-navy-200 bg-navy-50 text-navy-800 hover:bg-navy-100 px-3 py-1.5 text-xs font-semibold">
            <span class="material-symbols-outlined text-sm">add</span>
            Tambah baris
        </button>
    </div>
    <p class="text-xs text-slate-500 mb-3">
        Rinci investasi Anda per item — menggantikan isian "Total investasi" tunggal.
        <strong>Realisasi aktual diisi manual</strong> dan dikonfirmasi ulang tiap evaluasi 3 bulan;
        nilainya mengikuti harga pasar, jadi tidak bisa dihitung otomatis dari transaksi harian.
    </p>
    <input type="hidden" name="snapshot[investment_register_submitted]" value="1">
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-xs sm:text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-2 py-2 text-left font-medium">Jenis investasi</th>
                    <th class="px-2 py-2 text-left font-medium">Tujuan</th>
                    <th class="px-2 py-2 text-left font-medium">Mulai tahun</th>
                    <th class="px-2 py-2 text-left font-medium">Target investasi (tahun)</th>
                    <th class="px-2 py-2 text-left font-medium">Target nominal investasi (Rp)</th>
                    <th class="px-2 py-2 text-left font-medium">Realisasi pokok (Rp)</th>
                    <th class="px-2 py-2 text-left font-medium">Realisasi aktual (Rp)</th>
                    <th class="px-2 py-2 text-left font-medium">Yield</th>
                    <th class="px-2 py-2 text-left font-medium">Terakhir diupdate</th>
                    <th class="px-2 py-2 text-right font-medium w-12"></th>
                </tr>
            </thead>
            <tbody id="investment-register-body">
            @foreach($investments as $i => $row)
                @php
                    $rowYield = InvestmentRegister::yieldPercent(
                        ($row['realized_principal'] ?? '') === '' ? null : (int) $row['realized_principal'],
                        ($row['realized_actual'] ?? '') === '' ? null : (int) $row['realized_actual'],
                    );
                @endphp
                <tr class="border-t border-slate-100 investment-register-row" data-investment-row>
                    <td class="px-2 py-2">
                        <input type="hidden" name="snapshot[investment_register][{{ $i }}][investment_id]" value="{{ $row['investment_id'] ?? '' }}">
                        <select name="snapshot[investment_register][{{ $i }}][investment_type]"
                                class="w-full rounded border-slate-300 text-sm">
                            @foreach($investmentTypes as $value => $label)
                                <option value="{{ $value }}" @selected(($row['investment_type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="snapshot[investment_register][{{ $i }}][purpose]"
                               value="{{ $row['purpose'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="Dana Pensiun">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[investment_register][{{ $i }}][start_year]" min="1980" max="{{ now()->year + 1 }}" step="1"
                               value="{{ $row['start_year'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="2025">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[investment_register][{{ $i }}][target_years]" min="1" max="80" step="1"
                               value="{{ $row['target_years'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="21">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[investment_register][{{ $i }}][target_amount]" min="0" step="1000"
                               value="{{ $row['target_amount'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="5000000000">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[investment_register][{{ $i }}][realized_principal]" min="0" step="1000"
                               value="{{ $row['realized_principal'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="100000000">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[investment_register][{{ $i }}][realized_actual]" min="0" step="1000"
                               value="{{ $row['realized_actual'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="80000000">
                    </td>
                    {{-- Sengaja <td>, bukan input: yield tidak pernah dikirim user,
                         selalu diturunkan dari dua kolom realisasi di kirinya. --}}
                    <td class="px-2 py-2 font-semibold whitespace-nowrap {{ $yieldClass($rowYield) }}" data-investment-yield>
                        {{ InvestmentRegister::formatYield($rowYield) }}
                    </td>
                    <td class="px-2 py-2 text-slate-500 whitespace-nowrap">
                        {{ InvestmentRegister::formatDate($row['last_updated'] ?? null) }}
                    </td>
                    <td class="px-2 py-2 text-right align-middle">
                        <button type="button" class="investment-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1"
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
                    <td class="px-2 py-2 font-semibold text-navy-800" colspan="5">Total</td>
                    <td class="px-2 py-2 font-bold text-navy-800" id="investment-total-principal"
                        data-investment-total-principal>{{ number_format($investmentTotalPrincipal, 0, ',', '.') }}</td>
                    <td class="px-2 py-2 font-bold text-navy-800" id="investment-total-actual"
                        data-investment-total-actual>{{ number_format($investmentTotalActual, 0, ',', '.') }}</td>
                    <td class="px-2 py-2 font-bold whitespace-nowrap {{ $yieldClass($investmentTotalYield) }}" id="investment-total-yield"
                        data-investment-total-yield>{{ InvestmentRegister::formatYield($investmentTotalYield) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="text-[11px] text-slate-400 mt-2">
        Contoh: Emas · Dana Pensiun · 2025 · 21 · 5.000.000.000 · 100.000.000 · 80.000.000 · -20%.
        Yield = realisasi aktual dibanding realisasi pokok, jadi angka negatif berarti nilainya sedang di bawah modal.
        Bisa isi sampai {{ $maxInvestments }} baris. "Terakhir diupdate" terisi otomatis saat Anda mengubah realisasi pokok atau aktual.
    </p>
</div>

@once
@push('scripts')
<script>
(() => {
    const body = document.getElementById('investment-register-body');
    const addBtn = document.getElementById('investment-add-row');
    if (!body || !addBtn) return;

    const maxRows = {{ $maxInvestments }};
    const typeOptions = @json($investmentTypes);

    const totalPrincipalCell = document.getElementById('investment-total-principal');
    const totalActualCell = document.getElementById('investment-total-actual');
    const totalYieldCell = document.getElementById('investment-total-yield');
    const rupiah = new Intl.NumberFormat('id-ID');

    function intOrNull(input) {
        if (!input || input.value === '') return null;
        const value = parseInt(input.value, 10);
        return Number.isNaN(value) ? null : value;
    }

    // Cerminan InvestmentRegister::yieldPercent() — null (bukan 0%) kalau tidak
    // ada dasar pembanding, supaya JS dan PHP tidak pernah berbeda jawaban.
    function yieldPercent(principal, actual) {
        if (principal === null || principal === 0 || actual === null) return null;
        // Selisih dulu baru dibagi — sama persis dengan PHP-nya, supaya angka
        // yang tampil saat mengetik identik dengan yang tampil setelah simpan.
        return Math.round((actual - principal) / principal * 100 * 10000) / 10000;
    }

    function formatYield(value) {
        if (value === null) return '—';
        let text = value.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        if (text.endsWith(',0')) text = text.slice(0, -2);
        return text + '%';
    }

    function paintYield(cell, value) {
        cell.textContent = formatYield(value);
        cell.classList.remove('text-slate-500', 'text-rose-600', 'text-emerald-700');
        cell.classList.add(value === null ? 'text-slate-500' : (value < 0 ? 'text-rose-600' : 'text-emerald-700'));
    }

    function recalcRow(row) {
        const cell = row.querySelector('[data-investment-yield]');
        if (!cell) return;
        paintYield(cell, yieldPercent(
            intOrNull(row.querySelector('input[name*="[realized_principal]"]')),
            intOrNull(row.querySelector('input[name*="[realized_actual]"]')),
        ));
    }

    function recalcTotals() {
        let principal = 0;
        let actual = 0;
        body.querySelectorAll('[data-investment-row]').forEach((row) => {
            principal += intOrNull(row.querySelector('input[name*="[realized_principal]"]')) || 0;
            actual += intOrNull(row.querySelector('input[name*="[realized_actual]"]')) || 0;
        });
        if (totalPrincipalCell) totalPrincipalCell.textContent = rupiah.format(principal);
        if (totalActualCell) totalActualCell.textContent = rupiah.format(actual);
        // Yield total ditimbang uang (total aktual vs total pokok), bukan rata-rata
        // persen antar baris — rata-rata membuat posisi kecil sebobot posisi besar.
        if (totalYieldCell) paintYield(totalYieldCell, yieldPercent(principal || null, principal ? actual : null));
    }

    function recalcAll() {
        body.querySelectorAll('[data-investment-row]').forEach(recalcRow);
        recalcTotals();
    }

    // Delegated: baris baru ikut terpantau tanpa perlu bind ulang per input.
    body.addEventListener('input', (event) => {
        if (event.target.matches('input[name*="[realized_principal]"], input[name*="[realized_actual]"]')) {
            const row = event.target.closest('[data-investment-row]');
            if (row) recalcRow(row);
            recalcTotals();
        }
    });

    function reindexRows() {
        Array.from(body.querySelectorAll('[data-investment-row]')).forEach((row, i) => {
            row.querySelectorAll('input[name], select[name]').forEach((input) => {
                // Nama input berbentuk snapshot[investment_register][N][field] — pola harus
                // menyertakan "]" sebelum indeks, kalau tidak regex tidak pernah cocok
                // dan indeks bentrok setelah hapus+tambah (satu baris hilang saat simpan).
                input.name = input.name.replace(/investment_register]\[\d+]/, 'investment_register][' + i + ']');
            });
        });
        const count = body.querySelectorAll('[data-investment-row]').length;
        addBtn.disabled = count >= maxRows;
        addBtn.classList.toggle('opacity-50', count >= maxRows);
        recalcAll();
    }

    function bindRemove(btn) {
        btn.addEventListener('click', () => {
            const rows = body.querySelectorAll('[data-investment-row]');
            if (rows.length <= 1) {
                const row = btn.closest('[data-investment-row]');
                if (row) {
                    row.querySelectorAll('input').forEach((el) => { el.value = ''; });
                    row.querySelectorAll('select').forEach((el) => { el.selectedIndex = 0; });
                }
                reindexRows();
                return;
            }
            const row = btn.closest('[data-investment-row]');
            if (row) row.remove();
            reindexRows();
        });
    }

    body.querySelectorAll('.investment-remove-row').forEach(bindRemove);

    addBtn.addEventListener('click', () => {
        const count = body.querySelectorAll('[data-investment-row]').length;
        if (count >= maxRows) return;

        const i = count;
        const options = Object.entries(typeOptions)
            .map(([v, l]) => '<option value="' + v + '">' + l + '</option>').join('');
        const name = (field) => 'snapshot[investment_register][' + i + '][' + field + ']';
        const tr = document.createElement('tr');
        tr.className = 'border-t border-slate-100 investment-register-row';
        tr.setAttribute('data-investment-row', '');
        tr.innerHTML =
            '<td class="px-2 py-2"><input type="hidden" name="' + name('investment_id') + '" value=""><select name="' + name('investment_type') + '" class="w-full rounded border-slate-300 text-sm">' + options + '</select></td>' +
            '<td class="px-2 py-2"><input type="text" name="' + name('purpose') + '" class="w-full rounded border-slate-300 text-sm" placeholder="Dana Pensiun"></td>' +
            '<td class="px-2 py-2"><input type="number" name="' + name('start_year') + '" min="1980" max="{{ now()->year + 1 }}" step="1" class="w-full rounded border-slate-300 text-sm" placeholder="2025"></td>' +
            '<td class="px-2 py-2"><input type="number" name="' + name('target_years') + '" min="1" max="80" step="1" class="w-full rounded border-slate-300 text-sm" placeholder="21"></td>' +
            '<td class="px-2 py-2"><input type="number" name="' + name('target_amount') + '" min="0" step="1000" class="w-full rounded border-slate-300 text-sm" placeholder="5000000000"></td>' +
            '<td class="px-2 py-2"><input type="number" name="' + name('realized_principal') + '" min="0" step="1000" class="w-full rounded border-slate-300 text-sm" placeholder="100000000"></td>' +
            '<td class="px-2 py-2"><input type="number" name="' + name('realized_actual') + '" min="0" step="1000" class="w-full rounded border-slate-300 text-sm" placeholder="80000000"></td>' +
            '<td class="px-2 py-2 font-semibold whitespace-nowrap text-slate-500" data-investment-yield>&mdash;</td>' +
            '<td class="px-2 py-2 text-slate-500 whitespace-nowrap">&mdash;</td>' +
            '<td class="px-2 py-2 text-right align-middle"><button type="button" class="investment-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1" aria-label="Hapus baris" title="Hapus baris"><span class="material-symbols-outlined text-base">close</span></button></td>';
        body.appendChild(tr);
        bindRemove(tr.querySelector('.investment-remove-row'));
        reindexRows();
    });

    reindexRows();
})();
</script>
@endpush
@endonce
