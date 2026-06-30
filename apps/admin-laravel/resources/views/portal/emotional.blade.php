@extends('portal.layouts.app')

@section('title', 'Emotional Scan — YFD')
@section('heading', 'Emotional Spending Dashboard')

@section('content')
@php
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $hasData = $assessment['expense_count'] > 0;
@endphp

@if(!$hasData)
    @include('portal.partials.empty-state', [
        'title' => 'Belum ada data emosi',
        'message' => 'Emotional scan terisi setelah ada transaksi pengeluaran dengan mood & flag impulsif dari bot Telegram.',
    ])
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-rose-600">bolt</span>
            <h3 class="font-bold text-navy-800 text-lg">Penilaian Impulsifitas</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <div class="text-4xl font-extrabold text-navy-800">{{ $assessment['score'] }}<span class="text-base text-slate-400">/100</span></div>
                <div class="text-sm font-bold text-emerald-700 mt-1">{{ $assessment['grade'] }}</div>
                <div class="text-xs text-slate-500 mt-1">Skor kontrol diri</div>
            </div>
            <div class="rounded-xl bg-rose-50 p-4 text-center">
                <div class="text-3xl font-extrabold text-rose-600">{{ $assessment['impulsive_rate'] }}%</div>
                <div class="text-xs text-slate-600 mt-1">Impulsive Rate</div>
                <div class="text-xs font-semibold text-rose-700 mt-1">Risiko: {{ $assessment['risk_label'] }}</div>
            </div>
            <div class="rounded-xl bg-amber-50 p-4 text-center">
                <div class="text-3xl font-extrabold text-amber-700">{{ $assessment['impulsive_amount_share'] }}%</div>
                <div class="text-xs text-slate-600 mt-1">Nominal impulsif</div>
                <div class="text-xs text-slate-500 mt-1">{{ $fmt($assessment['impulsive_amount']) }} dari pengeluaran</div>
            </div>
        </div>
        <div class="mt-4 h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full {{ $assessment['impulsive_rate'] >= 30 ? 'bg-rose-500' : 'bg-emerald-500' }}"
                 style="width: {{ min(100, $assessment['impulsive_rate']) }}%"></div>
        </div>
        <p class="text-sm text-slate-600 mt-4 border-t pt-4 leading-relaxed">
            <span class="font-semibold text-navy-800">Doctor's Note:</span> {{ $assessment['doctors_note'] }}
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 text-center">
        <h3 class="font-bold text-navy-800 mb-4">Emotional Balance</h3>
        <div class="relative w-32 h-32 mx-auto rounded-full pulse-ring flex items-center justify-center"
             style="--score: {{ $assessment['emotional_balance']['score'] }}">
            <div class="w-24 h-24 rounded-full bg-white flex flex-col items-center justify-center shadow-inner">
                <span class="text-2xl font-extrabold">{{ $assessment['emotional_balance']['score'] }}</span>
                <span class="text-[10px] text-slate-400">/100</span>
            </div>
        </div>
        <div class="mt-3 text-sm font-semibold text-navy-800">{{ $assessment['emotional_balance']['label'] }}</div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Mood Calendar</h3>
        <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2 text-slate-400 font-semibold">
            @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $d)<div>{{ $d }}</div>@endforeach
        </div>
        @php
            $firstDow = \Carbon\Carbon::createFromFormat('Y-m', $assessment['month'])->dayOfWeek;
            $pad = $firstDow; // Sunday=0
        @endphp
        <div class="grid grid-cols-7 gap-1 text-center text-xs">
            @for($i = 0; $i < $pad; $i++)<div></div>@endfor
            @foreach($assessment['mood_calendar'] as $day)
                <div class="rounded-lg border py-1.5 {{ $day['mood'] ? 'bg-navy-800/5 border-navy-800/10' : 'border-transparent' }}">
                    <div class="text-slate-400 text-[10px]">{{ $day['day'] }}</div>
                    <div class="text-base leading-none">{{ $day['emoji'] ?: '·' }}</div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Mood Distribution</h3>
        <div class="h-64"><canvas id="moodChart"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Mood vs Spending</h3>
        <div class="h-64"><canvas id="moodSpendChart"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Mood vs Impulsive (%)</h3>
        <div class="h-64"><canvas id="moodImpulsiveChart"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Need vs Want</h3>
        <div class="h-56 flex items-center justify-center">
            <canvas id="needWantChart"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 mb-4">Need × Impulsive Matrix</h3>
        <div class="grid grid-cols-2 gap-3">
            @foreach($assessment['matrix'] as $cell)
                <div class="rounded-xl border p-4 {{ str_contains($cell['key'], 'impulsive') ? 'bg-rose-50/80 border-rose-200' : 'bg-emerald-50/80 border-emerald-200' }}">
                    <div class="text-xs font-bold text-slate-600">{{ $cell['label'] }}</div>
                    <div class="text-2xl font-extrabold text-navy-800 mt-1">{{ $cell['count'] }}</div>
                    <div class="text-xs text-slate-500">{{ $cell['share'] }}% transaksi</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
    <h3 class="font-bold text-navy-800 mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined">diagnosis</span> Behavioral Diagnosis
    </h3>
    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl bg-slate-50 p-4">
            <dt class="text-xs uppercase tracking-wider text-slate-500 font-bold">Dominant Mood</dt>
            <dd class="font-bold text-navy-800 mt-1">{{ $assessment['dominant_mood'] }}</dd>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
            <dt class="text-xs uppercase tracking-wider text-slate-500 font-bold">Dominant Pattern</dt>
            <dd class="font-bold text-navy-800 mt-1">{{ $assessment['dominant_pattern'] }}</dd>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
            <dt class="text-xs uppercase tracking-wider text-slate-500 font-bold">Highest Leakage</dt>
            <dd class="font-bold text-navy-800 mt-1">
                @if($assessment['highest_leakage'])
                    {{ $assessment['highest_leakage']['category'] }}
                    <span class="block text-sm font-normal text-rose-600">{{ $fmt($assessment['highest_leakage']['amount']) }}</span>
                @else — @endif
            </dd>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
            <dt class="text-xs uppercase tracking-wider text-slate-500 font-bold">Impulsive Rate</dt>
            <dd class="font-bold text-rose-600 mt-1 text-xl">{{ $assessment['impulsive_rate'] }}%</dd>
        </div>
    </dl>
</div>
@endsection

@push('scripts')
<script>
const byMood = @json($assessment['by_mood']);
const moodImpulsive = @json($assessment['mood_vs_impulsive']);
const needWant = @json($assessment['need_vs_want']);

if (byMood.length) {
    new Chart(document.getElementById('moodChart'), {
        type: 'doughnut',
        data: { labels: byMood.map(m => m.mood), datasets: [{ data: byMood.map(m => m.count), backgroundColor: ['#059669','#94a3b8','#3b82f6','#e11d48','#f97316','#64748b'] }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
    new Chart(document.getElementById('moodSpendChart'), {
        type: 'bar',
        data: { labels: byMood.map(m => m.mood), datasets: [{ label: 'Nominal', data: byMood.map(m => m.amount), backgroundColor: '#26528b', borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
}
if (moodImpulsive.length) {
    new Chart(document.getElementById('moodImpulsiveChart'), {
        type: 'bar',
        data: { labels: moodImpulsive.map(m => m.mood), datasets: [{ data: moodImpulsive.map(m => m.impulsive_rate), backgroundColor: '#e11d48', borderRadius: 6 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { max: 100 } } }
    });
}
new Chart(document.getElementById('needWantChart'), {
    type: 'doughnut',
    data: { labels: ['Need', 'Want'], datasets: [{ data: [needWant.need.count, needWant.want.count], backgroundColor: ['#059669', '#dca115'] }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>
@endpush
