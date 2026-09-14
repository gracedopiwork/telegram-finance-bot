@php
    use App\Support\AssetDetails;

    $existingBaseline = $existingBaseline ?? null;
    $blankAsset = ['asset_name' => '', 'asset_type' => '', 'asset_value' => ''];

    // Dibaca lewat rows() supaya data bentuk lama ({"rumah": 900000000}) tetap
    // tampil sebagai baris bernama, tanpa perlu migrasi data.
    $assets = AssetDetails::rows(old('snapshot.asset_details', $existingBaseline?->asset_details ?? []));
    while (count($assets) < 2) {
        $assets[] = $blankAsset;
    }
    $maxAssets = AssetDetails::MAX_ROWS;
    $assetTypes = AssetDetails::types();

    // Dihitung server-side dulu supaya angkanya sudah benar sebelum JS jalan.
    $assetTotal = array_sum(array_map(
        fn ($row) => (int) ($row['asset_value'] ?? 0),
        array_filter($assets, 'is_array'),
    ));
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <div class="text-sm font-semibold text-navy-800">Rincian aset</div>
        <button type="button" id="asset-add-row"
                class="inline-flex items-center gap-1 rounded-lg border border-navy-200 bg-navy-50 text-navy-800 hover:bg-navy-100 px-3 py-1.5 text-xs font-semibold">
            <span class="material-symbols-outlined text-sm">add</span>
            Tambah baris
        </button>
    </div>
    <p class="text-xs text-slate-500 mb-3">
        Rinci aset Anda per item — beri nama supaya dua aset sejenis tidak tercampur.
        Bisa isi sampai {{ $maxAssets }} baris. Total aset dihitung otomatis dari baris di bawah.
    </p>

    {{-- Penanda tabel terkirim: tanpa ini, user yang mengosongkan semua aset
         akan mendapat nilai lamanya kembali dari mergeExistingSnapshotFields(). --}}
    <input type="hidden" name="snapshot[asset_details_submitted]" value="1">

    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-xs sm:text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-2 py-2 text-left font-medium">Nama aset</th>
                    <th class="px-2 py-2 text-left font-medium">Jenis aset</th>
                    <th class="px-2 py-2 text-left font-medium">Nilai (Rp)</th>
                    <th class="px-2 py-2 text-right font-medium w-12"></th>
                </tr>
            </thead>
            <tbody id="asset-details-body">
            @foreach($assets as $i => $row)
                <tr class="border-t border-slate-100 asset-details-row" data-asset-row>
                    <td class="px-2 py-2">
                        <input type="text" name="snapshot[asset_details][{{ $i }}][asset_name]"
                               value="{{ $row['asset_name'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="Rumah">
                    </td>
                    <td class="px-2 py-2">
                        <select name="snapshot[asset_details][{{ $i }}][asset_type]"
                                class="w-full rounded border-slate-300 text-sm">
                            @foreach($assetTypes as $value => $label)
                                <option value="{{ $value }}" @selected(($row['asset_type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="snapshot[asset_details][{{ $i }}][asset_value]" min="0" step="1000"
                               value="{{ $row['asset_value'] ?? '' }}"
                               class="w-full rounded border-slate-300 text-sm" placeholder="900000000">
                    </td>
                    <td class="px-2 py-2 text-right align-middle">
                        <button type="button" class="asset-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1"
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
                         terkirim dan tidak bisa diisi user. Total aset murni turunan
                         dari baris di atas. --}}
                    <td class="px-2 py-2 font-semibold text-navy-800" colspan="2">Total aset</td>
                    <td class="px-2 py-2 font-bold text-navy-800" id="asset-total"
                        data-asset-total>{{ number_format($assetTotal, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="text-[11px] text-slate-400 mt-2">
        Contoh: Rumah Bogor · Rumah · 900.000.000
    </p>
</div>

@once
@push('scripts')
<script>
(() => {
    const body = document.getElementById('asset-details-body');
    const addBtn = document.getElementById('asset-add-row');
    if (!body || !addBtn) return;

    const maxRows = {{ $maxAssets }};
    const typeOptions = @json($assetTypes);
    const totalCell = document.getElementById('asset-total');
    const rupiah = new Intl.NumberFormat('id-ID');

    function recalcTotal() {
        if (!totalCell) return;
        let total = 0;
        body.querySelectorAll('input[name*="[asset_value]"]').forEach((input) => {
            const value = parseInt(input.value, 10);
            if (!Number.isNaN(value)) total += value;
        });
        totalCell.textContent = rupiah.format(total);
    }

    // Delegated: baris baru ikut terpantau tanpa perlu bind ulang per input.
    body.addEventListener('input', (event) => {
        if (event.target.matches('input[name*="[asset_value]"]')) recalcTotal();
    });

    function reindexRows() {
        Array.from(body.querySelectorAll('[data-asset-row]')).forEach((row, i) => {
            row.querySelectorAll('input[name], select[name]').forEach((input) => {
                // Nama input berbentuk snapshot[asset_details][N][field] — pola harus
                // menyertakan "]" sebelum indeks, kalau tidak regex tidak pernah cocok
                // dan indeks bentrok setelah hapus+tambah (satu baris hilang saat simpan).
                input.name = input.name.replace(/asset_details]\[\d+]/, 'asset_details][' + i + ']');
            });
        });
        const count = body.querySelectorAll('[data-asset-row]').length;
        addBtn.disabled = count >= maxRows;
        addBtn.classList.toggle('opacity-50', count >= maxRows);
        recalcTotal();
    }

    function bindRemove(btn) {
        btn.addEventListener('click', () => {
            const rows = body.querySelectorAll('[data-asset-row]');
            if (rows.length <= 1) {
                // Baris terakhir tidak dihapus, cuma dikosongkan.
                const row = btn.closest('[data-asset-row]');
                if (row) {
                    row.querySelectorAll('input').forEach((el) => { el.value = ''; });
                    row.querySelectorAll('select').forEach((el) => { el.selectedIndex = 0; });
                }
                reindexRows();
                return;
            }
            const row = btn.closest('[data-asset-row]');
            if (row) row.remove();
            reindexRows();
        });
    }

    body.querySelectorAll('.asset-remove-row').forEach(bindRemove);

    addBtn.addEventListener('click', () => {
        const count = body.querySelectorAll('[data-asset-row]').length;
        if (count >= maxRows) return;

        const i = count;
        const options = Object.entries(typeOptions)
            .map(([v, l]) => '<option value="' + v + '">' + l + '</option>').join('');
        const name = (field) => 'snapshot[asset_details][' + i + '][' + field + ']';
        const tr = document.createElement('tr');
        tr.className = 'border-t border-slate-100 asset-details-row';
        tr.setAttribute('data-asset-row', '');
        tr.innerHTML =
            '<td class="px-2 py-2"><input type="text" name="' + name('asset_name') + '" class="w-full rounded border-slate-300 text-sm" placeholder="Rumah"></td>' +
            '<td class="px-2 py-2"><select name="' + name('asset_type') + '" class="w-full rounded border-slate-300 text-sm">' + options + '</select></td>' +
            '<td class="px-2 py-2"><input type="number" name="' + name('asset_value') + '" min="0" step="1000" class="w-full rounded border-slate-300 text-sm" placeholder="900000000"></td>' +
            '<td class="px-2 py-2 text-right align-middle"><button type="button" class="asset-remove-row inline-flex items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 p-1" aria-label="Hapus baris" title="Hapus baris"><span class="material-symbols-outlined text-base">close</span></button></td>';
        body.appendChild(tr);
        bindRemove(tr.querySelector('.asset-remove-row'));
        reindexRows();
    });

    reindexRows();
})();
</script>
@endpush
@endonce
