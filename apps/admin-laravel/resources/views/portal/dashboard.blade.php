@extends('portal.layouts.app')

@section('title', 'Dashboard Keuangan — YFD')
@section('heading', 'Dashboard')

@section('content')
@php
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $cleanText = function (?string $text): string {
        $v = trim((string) $text);
        if ($v === '') {
            return '';
        }
        $v = preg_replace('/(?<=\d)\s*[mM]\b/u', ' jt', $v) ?? $v;
        $v = str_ireplace(['financial pulse', 'pulse score', 'kpi pulse'], 'ringkasan keuangan', $v);
        return $v;
    };
    $bucketDirections = [
        'Essential Living' => 'maksimal',
        'Future Building' => 'minimum',
        'Protection' => 'maksimal',
        'Flexible + Social' => 'maksimal',
    ];
    $bucketGuides = [];
    foreach ($summary['buckets'] ?? [] as $b) {
        $name = $b['bucket'] ?? '';
        $ideal = $b['ideal'] ?? null;
        $dir = $bucketDirections[$name] ?? null;
        if ($dir !== null && $ideal !== null) {
            $bucketGuides[$name] = $dir.' '.$ideal.'%';
        }
    }
    $note = $summary['doctors_note'];
    $noteRecommendations = is_array($note) ? ($note['findings'] ?? []) : [];
    $notePriority = is_array($note) ? trim((string) ($note['priority'] ?? '')) : '';
    $noteSummary = is_array($note) ? ($note['summary'] ?? '') : (string) $note;
    $doctorsPending = (bool) ($summary['doctors_pending'] ?? false)
        || str_contains((string) $noteSummary, 'akan dirilis')
        || str_contains((string) $noteSummary, 'akan dibuat');
    // Clinical summary mingguan tetap tampil selama Doctor's Note bulanan belum rilis.
    $showDoctorsNote = ! $doctorsPending
        && ($noteRecommendations !== [] || $notePriority !== '');
    $doctorsGeneratedAt = !empty($summary['doctors_generated_at']) ? \Carbon\Carbon::parse($summary['doctors_generated_at']) : null;
    $clinicalGeneratedAt = !empty($summary['clinical_generated_at']) ? \Carbon\Carbon::parse($summary['clinical_generated_at']) : null;
    $monthCarbon = \Carbon\Carbon::createFromFormat('Y-m', $summary['month'] ?? now()->format('Y-m'))->startOfMonth();
    $clinicalAnchor = $monthCarbon->isCurrentMonth() ? now() : $monthCarbon->copy()->endOfMonth();
    $clinicalWeek = \App\Models\PortalGuidanceSnapshot::monthCumulativeWeekNumber($clinicalAnchor);
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
        <div class="mb-3">
            @include('portal.partials.doctors-note-brand')
        </div>
        @if($showDoctorsNote)
            @if($noteRecommendations !== [])
                <ul class="space-y-2 text-sm text-slate-700 leading-relaxed mb-3">
                    @foreach($noteRecommendations as $recommendation)
                        <li class="flex gap-2"><span class="text-gold-500 font-bold">•</span><span>{{ $cleanText((string) $recommendation) }}</span></li>
                    @endforeach
                </ul>
            @elseif($notePriority !== '')
                <p class="text-sm text-slate-700 leading-relaxed mb-3">{{ $cleanText($notePriority) }}</p>
            @endif
            @if($doctorsGeneratedAt)
                <p class="text-xs text-slate-500">Terakhir dibuat: {{ $doctorsGeneratedAt->format('d/m/Y H:i') }}</p>
            @endif
            @include('portal.partials.ai-guidance-disclaimer')
        @else
            <p class="text-sm text-slate-600">
                Belum ada Doctor's Note untuk periode ini.
                Rekomendasi bulanan dirilis otomatis <strong>akhir bulan pukul 22.00 WIB</strong>
                dari Budget Prescription yang sama dengan clinical summary.
                Clinical summary minggu ke-{{ $clinicalWeek }} sudah memakai data aktual.
            </p>
            @include('portal.partials.ai-guidance-disclaimer')
        @endif
    </div>

    {{-- KPI --}}
    @include('portal.partials.financial-dashboard-kpi', ['summary' => $summary, 'fmt' => $fmt])

    {{-- Clinical Summary: disembunyikan saat Doctor's Note bulanan sudah dirilis (agar tidak dobel/membingungkan) --}}
    @if(! $showDoctorsNote)
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-3">Clinical Summary / Akumulasi minggu ke-{{ $clinicalWeek }}</div>
        <p class="text-xs text-slate-500 mb-3">Evaluasi mingguan tetap tampil sampai Doctor's Note bulanan dirilis akhir bulan.</p>
        @if(!empty($summary['clinical_summary']['headline']))
            <p class="text-base font-semibold text-navy-800 mb-2">{{ $cleanText((string) $summary['clinical_summary']['headline']) }}</p>
        @endif
        @if(!empty($summary['clinical_summary']['findings']))
            <ul class="space-y-1 text-sm text-slate-700">
                @foreach($summary['clinical_summary']['findings'] as $finding)
                    <li class="flex gap-2"><span>–</span><span>{{ $cleanText((string) $finding) }}</span></li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-slate-500">–</p>
        @endif
        @if($clinicalGeneratedAt)
            <p class="text-xs text-slate-500 mt-3">Terakhir dibuat: {{ $clinicalGeneratedAt->format('d/m/Y H:i') }}</p>
        @endif
    </div>
    @endif

    {{-- Kesehatan Pajak — referral tax planner (taxonomy v1.3 §5B.5) --}}
    @include('portal.partials.tax-health-panel', ['summary' => $summary, 'fmt' => $fmt])

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
                        <td class="py-2 text-right text-slate-600">
                            {{ $bucket['ideal'] }}%
                            @if(isset($bucketGuides[$bucket['bucket']]))
                                <span class="block text-[11px] text-slate-400">{{ $bucketGuides[$bucket['bucket']] }}</span>
                            @endif
                        </td>
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
            <div class="bg-white rounded-xl border border-slate-200 p-4 sm:col-span-2">
                <div class="text-sm font-semibold text-navy-800 mb-2">Protection analysis</div>
                <div class="h-40 flex items-center justify-center">
                    @if(!empty($summary['protection_analysis']))
                        <canvas id="protectionChart"></canvas>
                    @else
                        <p class="text-xs text-slate-400 text-center px-4">Belum ada transaksi BPJS/asuransi di periode ini. Catat premi lewat bot atau lengkapi tabel proteksi di Baseline.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Likuiditas Sosial — di bawah Budget Prescription, tidak mengubah 4 bucket (§5A) --}}
    @include('portal.partials.social-liquidity-panel', ['summary' => $summary, 'fmt' => $fmt])

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
        <div class="h-56">
            @if(collect($summary['trend'] ?? [])->sum(fn ($t) => ($t['income'] ?? 0) + ($t['expense'] ?? 0) + abs($t['cashflow'] ?? 0)) > 0)
                <canvas id="trendChart"></canvas>
            @else
                <p class="text-sm text-slate-500 flex items-center justify-center h-full">Belum ada data cashflow untuk ditampilkan.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
Chart.defaults.font.family = 'Manrope';
const chartColors = @json(config('yfd_brand.chart'));
const trend = @json($summary['trend']);
const buckets = @json($summary['buckets']);
const incomeSources = @json($summary['income_analysis']['by_source']);
const savingSources = @json($summary['saving_analysis'] ?? []);
const protectionSources = @json($summary['protection_analysis'] ?? []);
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

if (document.getElementById('trendChart') && trend.length > 0) {
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trend.map(t => t.label),
            datasets: [
                { label: 'Pendapatan', data: trend.map(t => t.income), borderColor: '{{ config('yfd_brand.mint') }}', tension: 0.35 },
                { label: 'Pengeluaran', data: trend.map(t => t.expense), borderColor: '#e11d48', tension: 0.35 },
                { label: 'Cashflow', data: trend.map(t => t.cashflow), borderColor: '{{ config('yfd_brand.navy_600') }}', borderDash: [4,4], tension: 0.35 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

doughnutChart('bucketChart', buckets.filter(b => b.amount > 0).map(b => b.bucket), buckets.filter(b => b.amount > 0).map(b => b.amount));
doughnutChart('incomeChart', incomeSources.map(c => c.label), incomeSources.map(c => c.amount));
doughnutChart('savingChart', savingSources.map(c => c.label), savingSources.map(c => c.amount));
doughnutChart('expenseChart', expenseCategories.map(c => c.category), expenseCategories.map(c => c.amount));
doughnutChart('protectionChart', protectionSources.map(c => c.label), protectionSources.map(c => c.amount));

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
