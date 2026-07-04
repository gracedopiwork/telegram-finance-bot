@php
    $baseline = $baseline ?? null;
    if ($baseline === null) {
        return;
    }
    $fmt = fn (int $n) => \App\Support\RupiahFormat::format($n);
    $hasSnapshot = trim((string) ($baseline->current_goal ?? '')) !== ''
        || collect(['avg_monthly_income', 'emergency_fund', 'cash_savings', 'total_investment', 'total_asset', 'total_debt'])
            ->contains(fn ($f) => $baseline->{$f} !== null && (int) $baseline->{$f} > 0)
        || $baseline->has_bpjs || $baseline->has_health_insurance
        || $baseline->has_income_protection || $baseline->has_life_insurance;
@endphp
@if($hasSnapshot)
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6">
    <div class="bg-slate-50 px-5 py-4 border-b flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-bold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined">inventory_2</span>
            Snapshot Keuangan Anda
        </h3>
        <a href="{{ route('portal.baseline.create', ['section' => 'snapshot']) }}"
           class="text-xs font-semibold text-navy-800 hover:underline">Perbarui</a>
    </div>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
        @foreach([
            'avg_monthly_income' => 'Pendapatan/bulan',
            'emergency_fund' => 'Dana darurat',
            'cash_savings' => 'Tabungan',
            'total_investment' => 'Investasi',
            'total_asset' => 'Total aset',
            'total_debt' => 'Total utang',
        ] as $field => $label)
            @if($baseline->{$field})
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                    <div class="font-bold text-navy-800">{{ $fmt((int) $baseline->{$field}) }}</div>
                </div>
            @endif
        @endforeach
    </div>
</div>
@endif
