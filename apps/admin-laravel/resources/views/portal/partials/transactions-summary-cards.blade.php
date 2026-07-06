@php
    $summary = $summary ?? [];
    $fmt = $fmt ?? fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
@endphp

<div class="grid grid-cols-2 sm:grid-cols-5 gap-4" @if(!empty($id)) id="{{ $id }}" @endif>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-2xl font-extrabold text-navy-800">{{ $summary['transaction_count'] ?? 0 }}</div>
        <div class="text-xs text-slate-500 mt-1">Total transaksi</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-emerald-700">{{ $fmt((int) ($summary['income'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Pemasukan</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-rose-600">{{ $fmt((int) ($summary['expense'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Pengeluaran</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-navy-600">{{ $fmt((int) ($summary['saving_investment'] ?? 0)) }}</div>
        <div class="text-xs text-slate-500 mt-1">Saving/Investment</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-navy-800">{{ $summary['saving_rate'] ?? 0 }}%</div>
        <div class="text-xs text-slate-500 mt-1">Alokasi saving · {{ $summary['period_label'] ?? '—' }}</div>
    </div>
</div>
