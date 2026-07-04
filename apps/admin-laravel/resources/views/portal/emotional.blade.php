@extends('portal.layouts.app')

@section('title', ($isFtsaOnlyPortalUser ?? false) ? 'Ringkasan FTSA — YFD' : 'Emotional Scan — YFD')
@section('heading', ($isFtsaOnlyPortalUser ?? false) ? 'Ringkasan FTSA Premium' : 'Behavioral Financial Dashboard')

@section('content')
@php
    $isFtsaOnly = $isFtsaOnlyPortalUser ?? false;
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $hasData = $assessment['expense_count'] > 0;
    $note = $assessment['doctors_note'];
    $noteSummary = is_array($note) ? ($note['summary'] ?? '') : (string) $note;
    $ftsaProfile = $assessment['ftsa_profile'] ?? null;
    $ftsaDomainMeta = config('baseline_assessment.ftsa_domains', []);
@endphp

@if($isFtsaOnly)
    @include('portal.partials.onboarding-banners')

    @include('portal.partials.ftsa-baseline-overview', [
        'baseline' => $baseline ?? null,
        'stageMeta' => $stageMeta ?? [],
        'showSnapshot' => false,
    ])

    @if($needsFtsa ?? false)
        @include('portal.partials.empty-state', [
            'title' => 'Lengkapi kuesioner FTSA 1–32',
            'message' => 'Isi semua pertanyaan untuk melihat archetype behavioral finansial dan skor domain CHD, RVD, SSD, ESD.',
        ])
        <div class="mt-4">
            <a href="{{ $portalFtsaUrl ?? route('portal.ftsa.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-5 py-3 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">edit_note</span>
                Isi FTSA Sekarang
            </a>
        </div>
    @elseif($ftsaProfile)
        @if(!empty($ftsaEndsAt))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm mb-6">
                Masa evaluasi FTSA berlaku hingga <strong>{{ $ftsaEndsAt->format('d M Y') }}</strong>.
                @if($ftsaRetakeLocked ?? false)
                    <span class="block mt-1">Pengisian ulang FTSA akan tersedia setelah tanggal tersebut.</span>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-end gap-3 mb-4">
            @if(!($ftsaRetakeLocked ?? false))
                <a href="{{ route('portal.ftsa.create') }}"
                   class="inline-flex items-center gap-2 border border-navy-800 text-navy-800 hover:bg-navy-50 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                    <span class="material-symbols-outlined text-lg">edit</span>
                    Isi Ulang FTSA
                </a>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs text-slate-600">
                    <span class="font-semibold text-navy-800">Terkunci</span> — evaluasi ulang setelah {{ $ftsaEndsAt?->format('d M Y') ?? 'masa berlaku habis' }}.
                </div>
            @endif
        </div>

        @include('portal.partials.ftsa-compact-summary', [
            'ftsaProfile' => $ftsaProfile,
            'baseline' => $baseline ?? null,
            'class' => 'mb-6',
        ])

        @include('portal.partials.ftsa-ai-guidance', ['ftsaAiGuidance' => $ftsaAiGuidance ?? []])

        @include('portal.partials.bot-upgrade-panel')
    @elseif(!($needsFtsa ?? false) && !($ftsaProfile ?? null))
        @include('portal.partials.empty-state', [
            'title' => 'FTSA belum diisi',
            'message' => 'Lengkapi kuesioner FTSA 1–32 untuk melihat archetype behavioral finansial dan insight AI.',
        ])
        <div class="mt-4">
            <a href="{{ $portalFtsaUrl ?? route('portal.ftsa.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-5 py-3 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">edit_note</span>
                Isi FTSA Sekarang
            </a>
        </div>
    @endif

@else
    @if(!($ftsaUnlocked ?? false))
        @include('portal.partials.ftsa-unlock-panel', [
            'variant' => 'banner',
            'message' => 'Behavioral dashboard tetap jalan. Unlock FTSA untuk insight dan rekomendasi yang lebih personal.',
        ])
    @endif

    @if(!$hasData)
        @include('portal.partials.empty-state', [
            'title' => 'Belum ada data emosi',
            'message' => 'Emotional scan terisi setelah ada transaksi pengeluaran dengan mood & flag impulsif dari YFD First Aid.',
        ])
    @endif

    @if($ftsaProfile)
        @include('portal.partials.ftsa-compact-summary', [
            'ftsaProfile' => $ftsaProfile,
            'baseline' => $baseline ?? null,
            'class' => 'mb-6',
        ])
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-rose-600">bolt</span>
                <h3 class="font-bold text-navy-800 text-lg">Penilaian Impulsifitas · {{ $assessment['period_label'] }}</h3>
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
            <div class="text-sm text-slate-600 mt-4 border-t pt-4 leading-relaxed space-y-2">
                @include('portal.partials.ai-source-badge', ['aiSource' => $assessment['ai_source'] ?? null])
                <p><span class="font-semibold text-navy-800">Doctor's Note:</span> {{ $noteSummary }}</p>
                @if(is_array($note) && !empty($note['priority']))
                    <p class="text-xs text-navy-800"><span class="font-semibold">Prioritas:</span> {{ $note['priority'] }}</p>
                @endif
            </div>
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
            <div class="grid grid-cols-3 gap-2 mt-4 text-xs">
                <div class="rounded-lg bg-emerald-50 p-2">
                    <div class="font-bold text-emerald-700">Positif</div>
                    <div>{{ $assessment['mood_groups']['positive']['share'] }}%</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-2">
                    <div class="font-bold text-slate-600">Netral</div>
                    <div>{{ $assessment['mood_groups']['neutral']['share'] }}%</div>
                </div>
                <div class="rounded-lg bg-rose-50 p-2">
                    <div class="font-bold text-rose-700">Negatif</div>
                    <div>{{ $assessment['mood_groups']['negative']['share'] }}%</div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($assessment['insights']))
    <div class="bg-gradient-to-r from-navy-800 to-navy-600 rounded-2xl p-5 text-white">
        <h3 class="font-bold mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-gold-400">lightbulb</span> Insight Behavioral
        </h3>
        @include('portal.partials.ai-source-badge', ['aiSource' => $assessment['ai_source'] ?? null, 'tone' => 'dark'])
        <ul class="space-y-2 text-sm text-white/90 mt-2">
            @foreach($assessment['insights'] as $insight)
                <li class="flex gap-2"><span class="text-gold-400">→</span>{{ $insight }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <h3 class="font-bold text-navy-800 mb-4">Mood Timeline</h3>
            <div class="h-64"><canvas id="moodTimelineChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <h3 class="font-bold text-navy-800 mb-4">Mood Calendar</h3>
            <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2 text-slate-400 font-semibold">
                @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $d)<div>{{ $d }}</div>@endforeach
            </div>
            @php $pad = \Carbon\Carbon::createFromFormat('Y-m', $assessment['month'])->dayOfWeek; @endphp
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
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <h3 class="font-bold text-navy-800 mb-4">Mood Spending Matrix</h3>
            <div class="h-64"><canvas id="moodSpendMatrixChart"></canvas></div>
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

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <h3 class="font-bold text-navy-800 mb-1">Rekomendasi Personal</h3>
            @include('portal.partials.ai-source-badge', ['aiSource' => $assessment['ai_source'] ?? null])
            <ul class="space-y-2 text-sm text-slate-700 mt-2">
                @foreach($assessment['recommendations']['personalized'] as $rec)
                    <li class="flex gap-2"><span class="text-navy-600 font-bold shrink-0">•</span>{{ $rec }}</li>
                @endforeach
            </ul>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <h3 class="font-bold text-navy-800 mb-1">Rekomendasi Umum</h3>
            @include('portal.partials.ai-source-badge', ['aiSource' => $assessment['ai_source'] ?? null])
            <ul class="space-y-2 text-sm text-slate-600 mt-2">
                @foreach($assessment['recommendations']['general'] as $rec)
                    <li class="flex gap-2"><span class="text-slate-400">•</span>{{ $rec }}</li>
                @endforeach
            </ul>
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
@endif
@endsection

@if(!($isFtsaOnlyPortalUser ?? false))
@push('scripts')
<script>
const moodTimeline = @json($assessment['mood_timeline']);
const moodMatrix = @json($assessment['mood_spending_matrix']);
const moodImpulsive = @json($assessment['mood_vs_impulsive']);
const needWant = @json($assessment['need_vs_want']);

if (moodTimeline.length) {
    const labels = moodTimeline.map(p => p.label);
    new Chart(document.getElementById('moodTimelineChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Mood Score', data: moodTimeline.map(p => p.mood_score), borderColor: '#26528b', tension: 0.3, spanGaps: true },
                { label: 'Pengeluaran', data: moodTimeline.map(p => p.expense), borderColor: '#e11d48', borderDash: [3,3], yAxisID: 'y1', tension: 0.3 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { min: 0, max: 5 }, y1: { position: 'right', grid: { drawOnChartArea: false } } },
            plugins: { legend: { position: 'bottom' } }
        }
    });
}
if (moodMatrix.length) {
    new Chart(document.getElementById('moodSpendMatrixChart'), {
        type: 'bar',
        data: {
            labels: moodMatrix.map(m => m.mood),
            datasets: [
                { label: 'Total', data: moodMatrix.map(m => m.amount), backgroundColor: '#26528b', borderRadius: 6 },
                { label: 'Impulsif', data: moodMatrix.map(m => m.impulsive_amount), backgroundColor: '#e11d48', borderRadius: 6 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
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
@endif
