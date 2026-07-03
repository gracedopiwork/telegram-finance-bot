@extends('portal.layouts.app')

@section('title', 'Dashboard Keuangan — YFD')
@section('heading', 'Financial Health Dashboard')

@section('content')
@php
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $hasData = $summary['transaction_count'] > 0;
    $note = $summary['doctors_note'];
    $noteSummary = is_array($note) ? ($note['summary'] ?? '') : (string) $note;
@endphp

@if(($hasBotPortalAccess ?? false) && ($needsFinancialDiagnostic ?? false) && !($isFtsaOnlyPortalUser ?? false))
    @include('portal.partials.onboarding-checklist', [
        'compact' => true,
        'skipActivation' => true,
        'diagnosticUrl' => $portalDiagnosticUrl ?? route('portal.diagnostic'),
    ])
@endif

@if(!($ftsaUnlocked ?? false) && ($hasBotPortalAccess ?? false) && !($isFtsaOnlyPortalUser ?? false))
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-amber-900">
            <div class="font-bold">FTSA Premium belum aktif</div>
            <div class="mt-0.5">Unlock kuesioner FTSA 1–32 dan rekomendasi personal selama <strong>12 bulan evaluasi</strong>.</div>
        </div>
        <a href="{{ route('checkout.show', ['code' => 'yfd-ftsa-premium']) }}"
           class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
            <span class="material-symbols-outlined text-lg">lock_open</span>
            Beli FTSA Premium
        </a>
    </div>
@endif

@if($summary['baseline_review_due'] ?? false)
    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 flex flex-wrap items-center justify-between gap-3">
        <span>Sudah 6 bulan sejak baseline terakhir. Evaluasi ulang untuk memperbarui tahap & prescription bucket.</span>
        <a href="{{ $portalBaselineUrl ?? route('portal.baseline.create') }}" class="font-semibold whitespace-nowrap">Perbarui Baseline →</a>
    </div>
@endif

@include('portal.partials.onboarding-banners')

@php
    $showFtsaSummary = false;
@endphp

@if($summary['baseline'])
    <div class="grid grid-cols-1 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Diagnostik Keuangan</div>
            <h3 class="text-xl font-extrabold text-navy-800 mt-1">{{ $summary['baseline']['stage_label'] ?? '—' }}</h3>
            <p class="text-sm text-slate-600 mt-2">
                Terakhir diisi: {{ $summary['baseline']['assessed_at'] ?? '—' }}
            </p>
        </div>
    </div>
@endif

@include('portal.partials.baseline-data-panel', [
    'baseline' => $summary['baseline'] ?? null,
    'existingBaseline' => $baselineRecord ?? null,
    'editUrl' => route('portal.baseline.create'),
    'embedSnapshotForm' => ($needsBaseline ?? false),
])

@if($hasBotPortalAccess ?? true)
<section id="input-data" class="scroll-mt-24 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-extrabold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-gold-500">edit_note</span>
            Input Data — Transaksi & Import
        </h2>
        <a href="{{ $portalTransactionsUrl ?? route('portal.transactions') }}"
           class="text-sm font-semibold text-navy-800 hover:underline shrink-0">
            Buka halaman Input Data →
        </a>
    </div>
    @include('portal.partials.transactions-input-panel', [
        'summary' => $summary,
        'fmt' => $fmt,
        'showBotBanner' => false,
        'dashboardLink' => false,
    ])
</section>
@include('portal.partials.transactions-delete-script', ['summary' => $summary])
@endif

@php $hasAssessment = ($summary['baseline'] ?? null); @endphp
@if(!$hasData)
    @include('portal.partials.empty-state', [
        'title' => ($needsFinancialDiagnostic ?? false) && !($isFtsaOnlyPortalUser ?? false)
            ? 'Mulai dari Diagnostik Keuangan'
            : ($hasAssessment ? 'Belum ada transaksi bot' : 'Dashboard masih kosong'),
        'message' => ($needsFinancialDiagnostic ?? false) && !($isFtsaOnlyPortalUser ?? false)
            ? 'Sebelum catat transaksi, isi diagnostik dulu — tahap keuangan & snapshot Anda dipakai untuk prescription bucket dan rekomendasi AI.'
            : ($hasAssessment
                ? 'Diagnostik dan FTSA Anda sudah tersimpan. Catat pemasukan & pengeluaran lewat YFD First Aid — metrik harian akan terisi otomatis.'
                : 'Mulai catat pemasukan & pengeluaran lewat YFD First Aid. Semua metrik di bawah akan terisi otomatis.'),
        'actionUrl' => ($needsFinancialDiagnostic ?? false) && !($isFtsaOnlyPortalUser ?? false)
            ? route('portal.baseline.create')
            : (($needsBaseline ?? false)
                ? (route('portal.dashboard', request()->only(['month', 'period'])) . '#baseline-snapshot')
                : route('portal.transactions')),
        'actionLabel' => ($needsBaseline ?? false) && !($needsFinancialDiagnostic ?? false)
            ? 'Isi Snapshot'
            : (($needsFinancialDiagnostic ?? false) ? 'Isi Baseline Data' : 'Buka Input Data'),
    ])
@endif

{{-- Clinical Summary --}}
@if($hasData)
<div class="rounded-2xl border p-5 sm:p-6
    @if($summary['clinical_summary']['status'] === 'healthy') bg-emerald-50 border-emerald-200
    @elseif($summary['clinical_summary']['status'] === 'critical') bg-rose-50 border-rose-200
    @else bg-sky-50 border-sky-200 @endif">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-2xl text-navy-800">clinical_notes</span>
        <div class="flex-1 min-w-0">
            <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Clinical Summary · {{ $summary['period_label'] }}</div>
            @include('portal.partials.ai-source-badge', ['aiSource' => $summary['ai_source'] ?? null])
            <h3 class="font-extrabold text-navy-800 text-lg mt-1">{{ $summary['clinical_summary']['headline'] }}</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                @foreach($summary['clinical_summary']['findings'] as $finding)
                    <li class="flex gap-2"><span class="text-navy-600">•</span>{{ $finding }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

{{-- KPI cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
    <x-portal.stat-card label="Total Pendapatan" :value="$fmt($summary['income'])" :hint="$summary['income_share'].'% dari total cash in'" icon="trending_up" tone="emerald" />
    <x-portal.stat-card label="Total Pengeluaran" :value="$fmt($summary['expense'])" :hint="$summary['expense_share'].'% dari pendapatan'" icon="shopping_cart" tone="rose" />
    <x-portal.stat-card label="Saving/Investment" :value="$fmt($summary['saving_investment'] ?? 0)" :hint="$summary['saving_rate'].'% dari pendapatan'" icon="savings" tone="navy" />
    <x-portal.stat-card label="Cashflow (Sisa)" :value="$fmt($summary['cashflow'])" :hint="$summary['cashflow_share'].'% dari pendapatan'" icon="account_balance" :tone="$summary['cashflow'] >= 0 ? 'emerald' : 'rose'" />
    <x-portal.stat-card label="Transaksi" :value="(string) $summary['transaction_count']" :hint="$summary['period_label']" icon="receipt_long" tone="navy" />
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- 4 Bucket --}}
    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-navy-800">medication</span>
                <h3 class="font-bold text-navy-800 text-lg">Budget Prescription (4 Bucket)</h3>
            </div>
            @php $stageKey = $summary['bucket_ideals_source'] ?? 'growing'; @endphp
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 bg-slate-100 px-2 py-1 rounded">
                Ideal: {{ ucfirst($stageKey) }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="pb-3 font-semibold">Bucket</th>
                        <th class="pb-3 font-semibold text-right">Aktual</th>
                        <th class="pb-3 font-semibold text-right">Ideal</th>
                        <th class="pb-3 font-semibold">Status</th>
                        <th class="pb-3 font-semibold w-32">Progress</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($summary['buckets'] as $bucket)
                    <tr class="border-b border-slate-50">
                        <td class="py-3 font-medium text-navy-800">{{ $bucket['bucket'] }}</td>
                        <td class="py-3 text-right">{{ $bucket['share'] }}% <span class="text-slate-400 text-xs">({{ $fmt($bucket['amount']) }})</span></td>
                        <td class="py-3 text-right text-slate-600">{{ $bucket['ideal'] }}%</td>
                        <td class="py-3">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded
                                @if(in_array($bucket['status'], ['met','on_target','within'])) bg-emerald-50 text-emerald-700
                                @elseif(in_array($bucket['status'], ['under_min','over_max','over','critical'])) bg-rose-50 text-rose-700
                                @else bg-amber-50 text-amber-700 @endif">
                                {{ $bucket['status_label'] }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $bucket['progress'] > 100 ? 'bg-rose-500' : 'bg-navy-500' }}"
                                     style="width: {{ min(100, $bucket['progress']) }}%"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Financial Pulse + Impulsivity teaser --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 text-center">
            <h3 class="font-bold text-navy-800 mb-4 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">ecg_heart</span> Financial Pulse
            </h3>
            <div class="relative w-36 h-36 mx-auto rounded-full pulse-ring flex items-center justify-center"
                 style="--score: {{ $summary['pulse']['score'] }}">
                <div class="w-28 h-28 rounded-full bg-white flex flex-col items-center justify-center shadow-inner">
                    <span class="text-3xl font-extrabold text-navy-800">{{ $summary['pulse']['score'] }}</span>
                    <span class="text-xs text-slate-400">/100</span>
                </div>
            </div>
            <div class="mt-3 inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold
                {{ $summary['pulse']['score'] >= 65 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                {{ $summary['pulse']['label'] }}
            </div>
            <div class="text-sm text-slate-600 mt-4 text-left border-t pt-4 leading-relaxed space-y-2">
                @include('portal.partials.ai-source-badge', ['aiSource' => $summary['ai_source'] ?? null])
                <p><span class="font-semibold text-navy-800">Doctor's Note:</span> {{ $noteSummary }}</p>
                @if(is_array($note))
                    @if(!empty($note['interpretation']))
                        <p class="text-xs"><span class="font-semibold">Interpretasi:</span> {{ $note['interpretation'] }}</p>
                    @endif
                    @if(!empty($note['priority']))
                        <p class="text-xs text-navy-800"><span class="font-semibold">Prioritas:</span> {{ $note['priority'] }}</p>
                    @endif
                @endif
            </div>
        </div>

        <a href="{{ route('portal.emotional', ['month' => $summary['month'], 'period' => $summary['period_months']]) }}"
           class="block bg-gradient-to-br from-navy-800 to-navy-600 rounded-2xl p-5 text-white shadow-sm hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wider text-gold-400 font-bold">Impulsivitas</div>
                    <div class="text-2xl font-extrabold mt-1">{{ $impulsivity['score'] }}/100</div>
                    <div class="text-sm text-white/80 mt-1">{{ $impulsivity['grade'] }}</div>
                </div>
                <span class="material-symbols-outlined text-4xl text-white/30">psychology</span>
            </div>
            <div class="mt-3 text-xs text-white/70 flex items-center gap-1">
                Lihat Emotional Scan
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </div>
        </a>
    </div>
</div>

{{-- Charts row --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Cashflow Trend <span class="text-slate-400 font-normal text-sm">({{ count($summary['trend']) }} bulan)</span></h3>
        <div class="h-64"><canvas id="trendChart"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Income Analysis</h3>
        <p class="text-xs text-slate-500 mb-3">{{ $summary['income_analysis']['stability'] }}</p>
        <div class="h-56 flex items-center justify-center">
            @if(empty($summary['income_analysis']['by_source']))
                <p class="text-sm text-slate-500">Belum ada pemasukan.</p>
            @else
                <canvas id="incomeChart"></canvas>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Spending by Category</h3>
        <div class="h-64 flex items-center justify-center">
            @if(empty($summary['by_category']))
                <p class="text-sm text-slate-500">Belum ada pengeluaran.</p>
            @else
                <canvas id="categoryChart"></canvas>
            @endif
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Top 10 Pengeluaran</h3>
        @if(empty($summary['top_expenses']))
            <p class="text-sm text-slate-500 py-8 text-center">Belum ada data.</p>
        @else
            <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                @foreach($summary['top_expenses'] as $i => $row)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-6 h-6 rounded-full bg-navy-800 text-white text-xs font-bold flex items-center justify-center shrink-0">{{ $i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-navy-800 truncate">{{ $row['category'] }}</div>
                            <div class="h-1.5 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                <div class="h-full bg-gold-400 rounded-full" style="width: {{ $row['share'] }}%"></div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-semibold">{{ $fmt($row['amount']) }}</div>
                            <div class="text-xs text-slate-500">{{ $row['share'] }}%</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
Chart.defaults.font.family = 'Manrope';
const trend = @json($summary['trend']);
const categories = @json($summary['by_category']);
const incomeSources = @json($summary['income_analysis']['by_source']);

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trend.map(t => t.label),
        datasets: [
            { label: 'Pendapatan', data: trend.map(t => t.income), borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.08)', fill: true, tension: 0.35 },
            { label: 'Pengeluaran', data: trend.map(t => t.expense), borderColor: '#e11d48', backgroundColor: 'rgba(225,29,72,.06)', fill: true, tension: 0.35 },
            { label: 'Cashflow', data: trend.map(t => t.cashflow), borderColor: '#26528b', borderDash: [4,4], tension: 0.35 },
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

if (incomeSources.length && document.getElementById('incomeChart')) {
    new Chart(document.getElementById('incomeChart'), {
        type: 'doughnut',
        data: {
            labels: incomeSources.map(c => c.label),
            datasets: [{
                data: incomeSources.map(c => c.amount),
                backgroundColor: ['#059669','#0c2240','#dca115','#26528b','#7c3aed'],
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } } }
    });
}

if (categories.length && document.getElementById('categoryChart')) {
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categories.map(c => c.category),
            datasets: [{
                data: categories.map(c => c.amount),
                backgroundColor: ['#0c2240','#26528b','#4d7ec0','#dca115','#e11d48','#059669','#7c3aed','#f97316','#64748b'],
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } } }
    });
}
</script>
@endpush
