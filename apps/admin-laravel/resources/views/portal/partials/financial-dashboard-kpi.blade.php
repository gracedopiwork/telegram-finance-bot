@php
    $summary = $summary ?? [];
    $fmt = $fmt ?? fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $cashLiq = $summary['cash_liquidity'] ?? [];
    $deficit = (int) ($cashLiq['deficit'] ?? 0);
    $borrowIn = (int) ($cashLiq['social_borrow_inflow'] ?? 0);
    $estimatedCash = (int) ($cashLiq['estimated_cash'] ?? ($summary['cashflow'] ?? 0));
    $outstandingDebt = (int) ($cashLiq['outstanding_debt'] ?? 0);
    $periodMonths = (int) ($cashLiq['period_months'] ?? ($summary['period_months'] ?? 1));
    $isSingleMonth = $periodMonths === 1;
    $estimatedCashLabel = $isSingleMonth
        ? 'Estimasi sisa kas bulan ini'
        : 'Estimasi sisa kas periode';
    $cashflowLabel = $isSingleMonth
        ? 'Cashflow bulan ini'
        : 'Cashflow periode';
    $showDeficitBlock = $deficit > 0 || $borrowIn > 0;
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-emerald-700">{{ $fmt((int) ($summary['income'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $isSingleMonth ? 'Pendapatan bulan ini' : 'Total pendapatan' }}</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-rose-600">{{ $fmt((int) ($summary['expense'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $isSingleMonth ? 'Pengeluaran bulan ini' : 'Pengeluaran' }}</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-navy-600">{{ $fmt((int) ($summary['saving_investment'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Saving</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold {{ ($summary['cashflow'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $fmt((int) ($summary['cashflow'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $cashflowLabel }}</div>
    </div>
</div>

@if($showDeficitBlock)
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
    @if($deficit > 0)
    <div class="bg-white rounded-xl border border-rose-100 p-4 text-center">
        <div class="text-lg font-extrabold text-rose-600">{{ $fmt($deficit) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $isSingleMonth ? 'Defisit bulan ini' : 'Defisit periode' }}</div>
    </div>
    @endif
    @if($borrowIn > 0)
    <div class="bg-white rounded-xl border border-amber-100 p-4 text-center">
        <div class="text-lg font-extrabold text-amber-700">{{ $fmt($borrowIn) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $isSingleMonth ? 'Utang masuk bulan ini' : 'Sumber defisit · Likuiditas Sosial' }}</div>
    </div>
    @endif
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold {{ $estimatedCash >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $fmt($estimatedCash) }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ $estimatedCashLabel }}</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-navy-800">{{ $fmt($outstandingDebt) }}</div>
        <div class="text-xs text-slate-500 mt-1">Outstanding utang sosial (semua periode)</div>
    </div>
</div>
@if(!empty($cashLiq['insight_text']))
<p class="mt-3 text-sm text-slate-600 leading-relaxed bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
    {{ $cashLiq['insight_text'] }}
</p>
@endif
@endif
