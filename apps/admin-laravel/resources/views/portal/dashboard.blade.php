@extends('portal.layouts.app')

@section('title', 'Dashboard Keuangan — YFD')
@section('heading', 'Dashboard')

@section('content')
@php
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $note = $summary['doctors_note'];
    $noteSummary = is_array($note) ? ($note['summary'] ?? '') : (string) $note;
    $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $summary['month'])->endOfMonth();
    $showDoctorsNote = $noteSummary !== '' && ! str_contains($noteSummary, 'akan dirilis') && ! str_contains($noteSummary, 'akan dibuat');
@endphp

<div class="space-y-5">
    {{-- Diagnostik keuangan — Financial stage saja --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-3">Diagnostik keuangan</div>
        <div class="text-2xl font-extrabold text-navy-800">{{ $summary['baseline']['stage_label'] ?? '—' }}</div>
        <div class="text-sm text-slate-500 mt-1">(Financial stage)</div>
    </div>

    {{-- Doctor's Note --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-3">Doctor's Note</div>
        @if($showDoctorsNote)
            <p class="text-sm text-slate-700 leading-relaxed mb-3">{{ $noteSummary }}</p>
        @endif
        <p class="text-sm text-slate-600">
            Rekomendasi dokter akan dibuat pada tgl <strong>{{ $monthEnd->format('d/m/Y') }}</strong> pukul <strong>22.00</strong>.
        </p>
    </div>

    {{-- KPI --}}
    @include('portal.partials.financial-dashboard-kpi', ['summary' => $summary, 'fmt' => $fmt])

    {{-- Clinical Summary / Minggu --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-3">Clinical Summary / Minggu ini</div>
        @if(!empty($summary['clinical_summary']['headline']))
            <p class="text-base font-semibold text-navy-800 mb-2">{{ $summary['clinical_summary']['headline'] }}</p>
        @endif
        @if(!empty($summary['clinical_summary']['findings']))
            <ul class="space-y-1 text-sm text-slate-700">
                @foreach($summary['clinical_summary']['findings'] as $finding)
                    <li class="flex gap-2"><span>–</span><span>{{ $finding }}</span></li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-slate-500">–</p>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        {{-- Budget prescription --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 min-h-[200px]">
            <div class="text-sm font-semibold text-navy-800 mb-4">Budget prescription</div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="pb-2 font-medium">Bucket</th>
                        <th class="pb-2 font-medium text-right">Aktual</th>
                        <th class="pb-2 font-medium text-right">Ideal</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($summary['buckets'] as $bucket)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 text-navy-800">{{ $bucket['bucket'] }}</td>
                        <td class="py-2 text-right">{{ $bucket['share'] }}%</td>
                        <td class="py-2 text-right text-slate-600">{{ $bucket['ideal'] }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-semibold text-navy-800 mb-2">Grafik 4 Bucket</div>
                <div class="h-40 flex items-center justify-center">
                    @if(collect($summary['buckets'])->sum('amount') > 0)
                        <canvas id="bucketChart"></canvas>
                    @endif
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-semibold text-navy-800 mb-2">Income analysis</div>
                <div class="h-40 flex items-center justify-center">
                    @if(!empty($summary['income_analysis']['by_source']))
                        <canvas id="incomeChart"></canvas>
                    @endif
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-semibold text-navy-800 mb-2">Saving analysis</div>
                <div class="h-40 flex items-center justify-center">
                    @if(!empty($summary['saving_analysis']))
                        <canvas id="savingChart"></canvas>
                    @endif
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-semibold text-navy-800 mb-2">Expense analysis</div>
                <div class="h-40 flex items-center justify-center">
                    @if(!empty($summary['by_category']))
                        <canvas id="expenseChart"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Grafik pengeluaran harian</div>
        <div class="h-52">
            @if(collect($summary['daily_expenses'] ?? [])->sum('amount') > 0)
                <canvas id="dailyExpenseChart"></canvas>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Analisis Cashflow Trend</div>
        <div class="h-56"><canvas id="trendChart"></canvas></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
Chart.defaults.font.family = 'Manrope';
const chartColors = ['#0c2240','#26528b','#4d7ec0','#dca115','#059669','#e11d48','#7c3aed'];
const trend = @json($summary['trend']);
const buckets = @json($summary['buckets']);
const incomeSources = @json($summary['income_analysis']['by_source']);
const savingSources = @json($summary['saving_analysis'] ?? []);
const expenseCategories = @json($summary['by_category']);
const dailyExpenses = @json($summary['daily_expenses'] ?? []);

function doughnutChart(canvasId, labels, data) {
    const el = document.getElementById(canvasId);
    if (!el || !labels.length) return;
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: chartColors, borderWidth: 2, borderColor: '#fff' }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
        },
    });
}

if (document.getElementById('trendChart')) {
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trend.map(t => t.label),
            datasets: [
                { label: 'Pendapatan', data: trend.map(t => t.income), borderColor: '#059669', tension: 0.35 },
                { label: 'Pengeluaran', data: trend.map(t => t.expense), borderColor: '#e11d48', tension: 0.35 },
                { label: 'Cashflow', data: trend.map(t => t.cashflow), borderColor: '#26528b', borderDash: [4,4], tension: 0.35 },
            ],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
}

doughnutChart('bucketChart', buckets.filter(b => b.amount > 0).map(b => b.bucket), buckets.filter(b => b.amount > 0).map(b => b.amount));
doughnutChart('incomeChart', incomeSources.map(c => c.label), incomeSources.map(c => c.amount));
doughnutChart('savingChart', savingSources.map(c => c.label), savingSources.map(c => c.amount));
doughnutChart('expenseChart', expenseCategories.map(c => c.category), expenseCategories.map(c => c.amount));

if (dailyExpenses.some(d => d.amount > 0) && document.getElementById('dailyExpenseChart')) {
    new Chart(document.getElementById('dailyExpenseChart'), {
        type: 'line',
        data: {
            labels: dailyExpenses.map(d => d.label),
            datasets: [{
                label: 'Pengeluaran',
                data: dailyExpenses.map(d => d.amount),
                borderColor: '#e11d48',
                tension: 0.2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { title: { display: true, text: 'Hari' } },
                y: { title: { display: true, text: 'Jumlah' } },
            },
            plugins: { legend: { display: false } },
        },
    });
}
</script>
@endpush
