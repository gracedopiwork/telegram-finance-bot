@php
    $existingBaseline = $existingBaseline ?? null;
    $snapshotValue = fn (string $field) => old("snapshot.{$field}", $existingBaseline?->{$field});
    $snapshotChecked = fn (string $field) => (bool) old("snapshot.{$field}", $existingBaseline?->{$field});
    $compact = $compact ?? false;
@endphp

<form method="post" action="{{ route('portal.baseline.store') }}" class="space-y-5">
    @csrf
    <div class="grid sm:grid-cols-2 gap-4 {{ $compact ? 'text-sm' : '' }}">
        <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-navy-800 mb-1">Current Goal (tujuan finansial saat ini)</label>
            <input type="text" name="snapshot[current_goal]" value="{{ $snapshotValue('current_goal') }}"
                   class="w-full rounded-lg border-slate-300 text-sm" placeholder="Contoh: Dana darurat 6 bulan + lunasi kartu kredit">
        </div>
        @foreach([
            'avg_monthly_income' => 'Rata-rata pendapatan bulanan (Rp)',
            'emergency_fund' => 'Dana darurat (Rp)',
            'cash_savings' => 'Cash / tabungan (Rp)',
            'total_investment' => 'Total investasi (Rp)',
            'total_asset' => 'Total aset (Rp)',
            'total_debt' => 'Total utang (Rp)',
        ] as $field => $label)
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                <input type="number" name="snapshot[{{ $field }}]" min="0" step="1000"
                       value="{{ $snapshotValue($field) }}"
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="0">
            </div>
        @endforeach
    </div>
    <fieldset>
        <legend class="text-sm font-semibold text-navy-800 mb-2">Proteksi yang dimiliki</legend>
        <div class="flex flex-wrap gap-4">
            @foreach([
                'has_bpjs' => 'BPJS',
                'has_health_insurance' => 'Asuransi kesehatan',
                'has_income_protection' => 'Income protection',
                'has_life_insurance' => 'Asuransi jiwa',
            ] as $field => $label)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="snapshot[{{ $field }}]" value="1"
                           class="rounded border-slate-300 text-navy-600"
                           @checked($snapshotChecked($field))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </fieldset>
    <button type="submit"
            class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">save</span>
        Simpan Snapshot Baseline
    </button>
</form>
