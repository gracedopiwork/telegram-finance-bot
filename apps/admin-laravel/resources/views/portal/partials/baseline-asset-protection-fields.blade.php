@php
    $existingBaseline = $existingBaseline ?? null;
    $assetDetails = old('snapshot.asset_details', $existingBaseline?->asset_details ?? []);
    if (! is_array($assetDetails)) {
        $assetDetails = [];
    }
    $policies = old('snapshot.protection_policies', $existingBaseline?->protection_policies ?? []);
    if (! is_array($policies) || $policies === []) {
        $policies = [
            ['type' => '', 'annual_premium' => '', 'coverage' => '', 'active_year' => '', 'payment_duration' => ''],
            ['type' => '', 'annual_premium' => '', 'coverage' => '', 'active_year' => '', 'payment_duration' => ''],
        ];
    }
    while (count($policies) < 2) {
        $policies[] = ['type' => '', 'annual_premium' => '', 'coverage' => '', 'active_year' => '', 'payment_duration' => ''];
    }
    $maxPolicies = 12;
@endphp

<div>
    <div class="text-sm font-semibold text-navy-800 mb-2">Rincian aset</div>
    <p class="text-xs text-slate-500 mb-3">Pecah total aset ke jenis utama (opsional).</p>
    <div class="grid sm:grid-cols-2 gap-3">
        @foreach([
            'rumah' => 'Rumah (Rp)',
            'tanah' => 'Tanah (Rp)',
            'apartemen' => 'Apartemen (Rp)',
            'mobil' => 'Mobil (Rp)',
            'lainnya' => 'Aset lain (Rp)',
        ] as $field => $label)
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ $label }}</label>
                <input type="number" name="snapshot[asset_details][{{ $field }}]" min="0" step="1000"
                       value="{{ $assetDetails[$field] ?? '' }}"
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="0">
            </div>
        @endforeach
    </div>
</div>

<div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <div class="text-sm font-semibold text-navy-800">Tabel proteksi (asuransi)</div>
        <button type="button" id="protection-add-row"
                class="inline-flex items-center gap-1 rounded-lg border border-navy-200 bg-navy-50 text-navy-800 hover:bg-navy-100 px-3 py-1.5 text-xs font-semibold">
            <span class="material-symbols-outlined text-sm">add</span>
            Tambah baris
        </button>
    </div>
    <p class="text-xs text-slate-500 mb-3">
        Format: jenis proteksi, premi tahunan, manfaat/coverage, tahun aktif, durasi bayar.
        Bisa isi sampai {{ $maxPolicies }} polis — tekan <strong>+</strong> jika asuransi lebih dari 2.
    </p>
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-xs sm:text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-2 py-2 text-left font-medium">Jenis proteksi</th>
                    <th class="px-2 py-2 text-left font-medium">Premi tahunan (Rp)</th>
                    <th class="px-2 py-2 text-left font-medium">Coverage / manfaat</th>
                    <th class="px-2 py-2 text-left font-medium">Tahun aktif</th>
                    <th class="px-2 py-2 text-left font-medium">Durasi bayar</th>
                    <th class="px-2 py-2 text-right font-medium w-12"></th>
                </tr>
            </thead>
            <tbody id="protection-policies-body">
            @foreach($policies as $i => $row)
                <tr class="border-t border-slate-100 protection-policy-row" data-protection-row>
                    <td class="px-2 py-2">
                        <input type="text" name="snapshot[protection_policies][{{ $i }}][type]"
                               value="{{ $row['type'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm"
                               placeholder="BPJS / Asuransi jiwa">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[protection_policies][{{ $i }}][annual_premium]" min="0" step="1000"
                               value="{{ $row['annual_premium'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="1800000">
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="snapshot[protection_policies][{{ $i }}][coverage]"
                               value="{{ $row['coverage'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm"
                               placeholder="Rp 500.000.000 / faskes">
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="snapshot[protection_policies][{{ $i }}][active_year]"
                               value="{{ $row['active_year'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="2020">
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="snapshot[protection_policies][{{ $i }}][payment_duration]"
                               value="{{ $row['payment_duration'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="5 tahun / Continue">
                    </td>
                    <td class="px-2 py-2 text-right align-middle">
                        <button type="button" class="protection-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1"
                                aria-label="Hapus baris" title="Hapus baris">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-[11px] text-slate-400 mt-2">
        Contoh: BPJS · 1.800.000 · Biaya pengobatan faskes dan RS · 2026 · Continue
    </p>
</div>

@once
@push('scripts')
<script>
(() => {
    const body = document.getElementById('protection-policies-body');
    const addBtn = document.getElementById('protection-add-row');
    if (!body || !addBtn) return;

    const maxRows = {{ $maxPolicies }};

    function reindexRows() {
        Array.from(body.querySelectorAll('[data-protection-row]')).forEach((row, i) => {
            row.querySelectorAll('input[name]').forEach((input) => {
                input.name = input.name.replace(/protection_policies\[\d+]/, 'protection_policies[' + i + ']');
            });
        });
        const count = body.querySelectorAll('[data-protection-row]').length;
        addBtn.disabled = count >= maxRows;
        addBtn.classList.toggle('opacity-50', count >= maxRows);
    }

    function bindRemove(btn) {
        btn.addEventListener('click', () => {
            const rows = body.querySelectorAll('[data-protection-row]');
            if (rows.length <= 1) {
                const row = btn.closest('[data-protection-row]');
                if (row) row.querySelectorAll('input').forEach((el) => { el.value = ''; });
                return;
            }
            const row = btn.closest('[data-protection-row]');
            if (row) row.remove();
            reindexRows();
        });
    }

    body.querySelectorAll('.protection-remove-row').forEach(bindRemove);

    addBtn.addEventListener('click', () => {
        const count = body.querySelectorAll('[data-protection-row]').length;
        if (count >= maxRows) return;

        const i = count;
        const tr = document.createElement('tr');
        tr.className = 'border-t border-slate-100 protection-policy-row';
        tr.setAttribute('data-protection-row', '');
        tr.innerHTML =
            '<td class="px-2 py-2"><input type="text" name="snapshot[protection_policies][' + i + '][type]" class="w-full rounded border-slate-300 text-sm" placeholder="BPJS / Asuransi jiwa"></td>' +
            '<td class="px-2 py-2"><input type="number" name="snapshot[protection_policies][' + i + '][annual_premium]" min="0" step="1000" class="w-full rounded border-slate-300 text-sm" placeholder="1800000"></td>' +
            '<td class="px-2 py-2"><input type="text" name="snapshot[protection_policies][' + i + '][coverage]" class="w-full rounded border-slate-300 text-sm" placeholder="Rp 500.000.000 / faskes"></td>' +
            '<td class="px-2 py-2"><input type="text" name="snapshot[protection_policies][' + i + '][active_year]" class="w-full rounded border-slate-300 text-sm" placeholder="2020"></td>' +
            '<td class="px-2 py-2"><input type="text" name="snapshot[protection_policies][' + i + '][payment_duration]" class="w-full rounded border-slate-300 text-sm" placeholder="5 tahun / Continue"></td>' +
            '<td class="px-2 py-2 text-right align-middle"><button type="button" class="protection-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1" aria-label="Hapus baris" title="Hapus baris"><span class="material-symbols-outlined text-base">close</span></button></td>';
        body.appendChild(tr);
        bindRemove(tr.querySelector('.protection-remove-row'));
        reindexRows();
    });

    reindexRows();
})();
</script>
@endpush
@endonce
