@php
    $baseline = $baseline ?? null;
    $fmt = $fmt ?? fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $editUrl = $editUrl ?? route('portal.baseline.create');
    $hasSnapshot = $baseline && ($baseline['has_financial_snapshot'] ?? false);
@endphp

@if($hasSnapshot)
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="bg-slate-50 px-5 py-4 border-b flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-bold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined">inventory_2</span>
            Baseline Data
        </h3>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <span>Diperbarui: {{ $baseline['assessed_at'] }} · {{ $baseline['stage_label'] }}</span>
            <a href="{{ $editUrl }}" class="font-semibold text-navy-800 hover:underline">Perbarui →</a>
        </div>
    </div>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        @if($baseline['current_goal'])
            <div class="sm:col-span-2 lg:col-span-4 rounded-xl bg-gold-50 border border-gold-200 p-3">
                <div class="text-xs font-bold text-amber-800 uppercase">Current Goal</div>
                <div class="font-medium text-navy-800 mt-1">{{ $baseline['current_goal'] }}</div>
            </div>
        @endif
        @foreach([
            'avg_monthly_income' => 'Pendapatan/bulan',
            'emergency_fund' => 'Dana darurat',
            'cash_savings' => 'Tabungan',
            'total_investment' => 'Investasi',
            'total_asset' => 'Total aset',
            'total_debt' => 'Total utang',
        ] as $key => $label)
            @if($baseline[$key])
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                    <div class="font-bold text-navy-800">{{ $fmt((int) $baseline[$key]) }}</div>
                </div>
            @endif
        @endforeach
        <div class="rounded-xl bg-slate-50 p-3">
            <div class="text-xs text-slate-500 mb-1">Proteksi</div>
            <div class="flex flex-wrap gap-1">
                @if($baseline['protection']['bpjs'] ?? false)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">BPJS</span>@endif
                @if($baseline['protection']['health'] ?? false)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Kesehatan</span>@endif
                @if($baseline['protection']['income'] ?? false)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Income</span>@endif
                @if($baseline['protection']['life'] ?? false)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Jiwa</span>@endif
            </div>
        </div>
    </div>
</div>
@elseif($baseline)
<div class="bg-white rounded-2xl border border-amber-300 shadow-sm overflow-hidden">
    <div class="bg-amber-50 px-5 py-4 border-b border-amber-200">
        <h3 class="font-bold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-700">edit_note</span>
            Lengkapi Snapshot Baseline
        </h3>
        <p class="text-sm text-amber-900 mt-1">
            Diagnostik: <strong>{{ $baseline['stage_label'] ?? '—' }}</strong> · Isi angka keuangan di bawah.
        </p>
    </div>
    <div class="p-5 sm:p-6">
        @if($showInlineForm ?? true)
            @include('portal.partials.baseline-snapshot-form', [
                'existingBaseline' => $existingBaseline ?? null,
                'compact' => true,
            ])
        @else
            <a href="{{ $editUrl }}" class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
                Buka form baseline <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        @endif
    </div>
</div>
@elseif(($needsFinancialDiagnostic ?? false) || ($needsBaseline ?? false))
<div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 flex flex-wrap items-center justify-between gap-3">
    <span>Belum ada baseline data. Isi diagnostik + snapshot sebelum analisis transaksi lebih akurat.</span>
    <a href="{{ $baselineUrl ?? route('portal.baseline.create') }}" class="font-semibold whitespace-nowrap hover:underline">Isi Baseline Data →</a>
</div>
@endif
