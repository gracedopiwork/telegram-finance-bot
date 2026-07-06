@php
    $summary = $summary ?? [];
    $fmt = $fmt ?? fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-emerald-700">{{ $fmt((int) ($summary['income'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Total pendapatan</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-rose-600">{{ $fmt((int) ($summary['expense'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Pengeluaran</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-navy-600">{{ $fmt((int) ($summary['saving_investment'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Saving</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold {{ ($summary['cashflow'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $fmt((int) ($summary['cashflow'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Cashflow</div>
    </div>
</div>
