@extends('portal.layouts.app')

@section('title', ($isFtsaOnlyPortalUser ?? false) ? 'Ringkasan FTSA — YFD' : 'Dashboard Behaviour — YFD')
@section('heading', ($isFtsaOnlyPortalUser ?? false) ? 'Ringkasan FTSA Premium' : 'Dashboard')

@section('content')
@php
    $isFtsaOnly = $isFtsaOnlyPortalUser ?? false;
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $hasData = $assessment['expense_count'] > 0;
    $ftsaProfile = $assessment['ftsa_profile'] ?? null;
    $moodLabel = fn (string $m) => match ($m) {
        'Neutral' => 'Netral',
        'Stressed' => 'Stres',
        'Angry' => 'Ang',
        default => $m,
    };
    $matrixOrder = ['need_impulsive', 'want_impulsive', 'need_planned', 'want_planned'];
    $matrixByKey = collect($assessment['matrix'] ?? [])->keyBy('key');
    $ftsaDoctorsNote = collect($ftsaAiGuidance['insights'] ?? [])->filter()->implode(' ');
@endphp

@if($isFtsaOnly)
    @include('portal.partials.onboarding-banners')

    @include('portal.partials.ftsa-baseline-overview', [
        'baseline' => $baseline ?? null,
        'stageMeta' => $stageMeta ?? [],
        'stageGuidance' => $stageGuidance ?? [],
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
                    Isi Ulang FTSA
                </a>
            @endif
        </div>

        @include('portal.partials.ftsa-compact-summary', [
            'ftsaProfile' => $ftsaProfile,
            'baseline' => $baseline ?? null,
            'class' => 'mb-6',
        ])

        @include('portal.partials.ftsa-ai-guidance', ['ftsaAiGuidance' => $ftsaAiGuidance ?? []])

        @include('portal.partials.bot-upgrade-panel')
    @else
        @include('portal.partials.empty-state', [
            'title' => 'FTSA belum diisi',
            'message' => 'Lengkapi kuesioner FTSA 1–32 untuk melihat archetype behavioral finansial.',
        ])
        <div class="mt-4">
            <a href="{{ $portalFtsaUrl ?? route('portal.ftsa.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-5 py-3 rounded-xl text-sm">
                Isi FTSA Sekarang
            </a>
        </div>
    @endif

@else
<div class="space-y-5">
    {{-- 1. Doctor's Note + FTSA (2 kolom) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-sm font-semibold text-navy-800 mb-3">Doctor's Note</div>
            @if($ftsaProfile)
                @if($ftsaDoctorsNote !== '')
                    <p class="text-sm text-slate-700 leading-relaxed">{{ $ftsaDoctorsNote }}</p>
                @else
                    <p class="text-sm text-slate-700 leading-relaxed">
                        Archetype dominan: <strong>{{ $ftsaProfile['archetype'] ?? '—' }}</strong>.
                    </p>
                @endif
            @elseif($ftsaUnlocked ?? false)
                <p class="text-sm text-slate-700 leading-relaxed">
                    Isi kuesioner FTSA 1–32 terlebih dahulu agar Doctor's Note behavioral bisa disusun dari profil Anda.
                </p>
            @else
                <p class="text-sm text-slate-700 leading-relaxed">
                    Unlock FTSA Premium untuk mendapatkan Doctor's Note behavioral yang dipersonalisasi dari profil FTSA Anda.
                </p>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-sm font-semibold text-navy-800 mb-3">FTSA</div>
            @if($ftsaProfile)
                <div class="text-lg font-extrabold text-navy-800">{{ $ftsaProfile['archetype'] ?? '—' }}</div>
                <div class="grid grid-cols-2 gap-2 mt-4">
                    @foreach($ftsaProfile['domains'] ?? [] as $domain)
                        <div class="rounded-lg bg-slate-50 px-3 py-2 text-center">
                            <div class="text-xs font-bold text-slate-500">{{ $domain['label'] ?? strtoupper($domain['key'] ?? '') }}</div>
                            <div class="text-base font-extrabold text-navy-800">{{ (int) ($domain['score'] ?? 0) }}<span class="text-xs text-slate-400">/40</span></div>
                        </div>
                    @endforeach
                </div>
                @if(!($ftsaRetakeLocked ?? false))
                    <a href="{{ $portalFtsaUrl ?? route('portal.ftsa.create') }}"
                       class="inline-block mt-4 text-sm font-semibold text-navy-800 hover:underline">
                        Lihat / isi ulang FTSA →
                    </a>
                @endif
            @elseif($ftsaUnlocked ?? false)
                <p class="text-sm text-slate-600 mb-4">FTSA Premium aktif. Lengkapi kuesioner 1–32 untuk melihat archetype dan skor domain.</p>
                <a href="{{ $portalFtsaUrl ?? route('portal.ftsa.create') }}"
                   class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2.5 rounded-xl text-sm">
                    Isi FTSA Sekarang
                </a>
            @else
                <p class="text-sm text-slate-600 mb-4">Beli FTSA Premium untuk membuka kuesioner 1–32 dan insight behavioral personal selama 12 bulan evaluasi.</p>
                @include('portal.partials.ftsa-unlock-panel', ['variant' => 'embedded'])
            @endif
        </div>
    </div>

    {{-- 2. Behavioral Recommendation --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-3">Behavioral Recommendation</div>
        @if(!empty($assessment['behavioral_recommendations']))
            <ul class="space-y-1 text-sm text-slate-700">
                @foreach($assessment['behavioral_recommendations'] as $rec)
                    <li class="flex gap-2"><span>–</span><span>{{ $rec }}</span></li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-slate-500">–</p>
        @endif
    </div>

    {{-- 3. Insight bulanan --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-3">Insight bulanan</div>
        <p class="text-xs text-slate-500 mb-2">{{ $assessment['month_label'] ?? $assessment['period_label'] }}</p>
        @if(!empty($assessment['insights']))
            <ul class="space-y-1 text-sm text-slate-700">
                @foreach($assessment['insights'] as $insight)
                    <li class="flex gap-2"><span>–</span><span>{{ $insight }}</span></li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-slate-500">–</p>
        @endif
    </div>

    {{-- 4. KPI --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-lg font-extrabold text-rose-600">{{ $assessment['impulsive_rate'] }}%</div>
            <div class="text-xs text-slate-500 mt-1">Rate impulsif</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-lg font-extrabold text-navy-800">{{ $moodLabel($assessment['dominant_mood']) }}</div>
            <div class="text-xs text-slate-500 mt-1">Mood dominan</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-lg font-extrabold text-amber-700">{{ $fmt($assessment['impulsive_amount']) }}</div>
            <div class="text-xs text-slate-500 mt-1">Nominal impulsif</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-lg font-extrabold text-navy-800">{{ $assessment['dominant_pattern'] }}</div>
            <div class="text-xs text-slate-500 mt-1">Dominan pattern</div>
        </div>
    </div>

    {{-- 5. Mood distribusi --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Mood distribusi</div>
        <div class="h-52 flex items-center justify-center">
            @if($hasData)
                <canvas id="moodDistribusiChart"></canvas>
            @endif
        </div>
        <div class="flex justify-center gap-4 mt-3 text-xs text-slate-600">
            <span>Positif {{ $assessment['mood_groups']['positive']['share'] }}%</span>
            <span>Netral {{ $assessment['mood_groups']['neutral']['share'] }}%</span>
            <span>Negatif {{ $assessment['mood_groups']['negative']['share'] }}%</span>
        </div>
    </div>

    {{-- 6. Mood berdasarkan pengeluaran --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Mood berdasarkan pengeluaran</div>
        <div class="h-52">
            @if(!empty($assessment['by_mood']))
                <canvas id="moodPengeluaranChart"></canvas>
            @endif
        </div>
    </div>

    {{-- 7. Mood vs jumlah transaksi --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Mood vs jumlah transaksi</div>
        <div class="h-52">
            @if(!empty($assessment['by_mood']))
                <canvas id="moodTransaksiChart"></canvas>
            @endif
        </div>
    </div>

    {{-- 8. Need vs Want impulsivitas --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Need vs Want impulsivitas</div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            @foreach($matrixOrder as $key)
                @php $cell = $matrixByKey->get($key); @endphp
                @if($cell)
                    <div class="rounded-xl border border-slate-200 p-3 text-center">
                        <div class="text-xs text-slate-500">{{ $cell['label'] }}</div>
                        <div class="text-xl font-extrabold text-navy-800 mt-1">{{ $cell['count'] }}</div>
                        <div class="text-xs text-slate-500">{{ $cell['share'] }}%</div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="h-48 flex items-center justify-center">
            @if($hasData)
                <canvas id="needWantChart"></canvas>
            @endif
        </div>
    </div>

    {{-- 9. Impulsive spending --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Impulsive spending</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="h-48 flex items-center justify-center">
                @if(!empty($assessment['impulsive_categories']))
                    <canvas id="impulsiveSpendingChart"></canvas>
                @endif
            </div>
            <ul class="space-y-2 text-sm text-slate-700">
                <li><span class="text-slate-500">Jumlah transaksi:</span> <strong>{{ $assessment['impulsive_count'] }}</strong></li>
                <li><span class="text-slate-500">Total nominal:</span> <strong>{{ $fmt($assessment['impulsive_amount']) }}</strong></li>
                <li><span class="text-slate-500">% total transaksi:</span> <strong>{{ $assessment['impulsive_rate'] }}%</strong></li>
                <li>
                    <span class="text-slate-500">Top kategori impulsif:</span>
                    @if(!empty($assessment['impulsive_categories']))
                        <ul class="mt-1 space-y-0.5">
                            @foreach($assessment['impulsive_categories'] as $cat)
                                <li>– {{ $cat['category'] }} ({{ $fmt($cat['amount']) }})</li>
                            @endforeach
                        </ul>
                    @else
                        <strong> –</strong>
                    @endif
                </li>
            </ul>
        </div>
    </div>

    {{-- 10. Mood spending matrix --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm font-semibold text-navy-800 mb-4">Mood spending matrix</div>
        @if(!empty($assessment['mood_table']))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="pb-2 font-medium">Mood</th>
                            <th class="pb-2 font-medium text-right">Transaksi</th>
                            <th class="pb-2 font-medium text-right">Total spending</th>
                            <th class="pb-2 font-medium text-right">Average / transaksi</th>
                            <th class="pb-2 font-medium text-right">% impulsif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assessment['mood_table'] as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-navy-800">{{ $moodLabel($row['mood']) }}</td>
                                <td class="py-2 text-right">{{ $row['count'] }}</td>
                                <td class="py-2 text-right">{{ $fmt($row['amount']) }}</td>
                                <td class="py-2 text-right">{{ $fmt($row['average']) }}</td>
                                <td class="py-2 text-right">{{ $row['impulsive_rate'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">–</p>
        @endif
    </div>
</div>
@endif
@endsection

@if(!($isFtsaOnlyPortalUser ?? false))
@push('scripts')
<script>
Chart.defaults.font.family = 'Manrope';
const chartColors = ['#0c2240','#26528b','#4d7ec0','#dca115','#059669','#e11d48','#7c3aed'];
const moodGroups = @json($assessment['mood_groups']);
const byMood = @json($assessment['by_mood']);
const needWant = @json($assessment['need_vs_want']);
const impulsiveCategories = @json($assessment['impulsive_categories'] ?? []);

const moodDisplay = (m) => ({ Neutral: 'Netral', Stressed: 'Stres', Angry: 'Ang' }[m] || m);

if (document.getElementById('moodDistribusiChart')) {
    new Chart(document.getElementById('moodDistribusiChart'), {
        type: 'doughnut',
        data: {
            labels: ['Positif', 'Netral', 'Negatif'],
            datasets: [{
                data: [
                    moodGroups.positive.share,
                    moodGroups.neutral.share,
                    moodGroups.negative.share,
                ],
                backgroundColor: ['#059669', '#94a3b8', '#e11d48'],
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
        },
    });
}

if (byMood.length && document.getElementById('moodPengeluaranChart')) {
    new Chart(document.getElementById('moodPengeluaranChart'), {
        type: 'bar',
        data: {
            labels: byMood.map(m => moodDisplay(m.mood)),
            datasets: [{
                label: 'Pengeluaran',
                data: byMood.map(m => m.amount),
                backgroundColor: '#26528b',
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

if (byMood.length && document.getElementById('moodTransaksiChart')) {
    new Chart(document.getElementById('moodTransaksiChart'), {
        type: 'bar',
        data: {
            labels: byMood.map(m => moodDisplay(m.mood)),
            datasets: [{
                label: 'Transaksi',
                data: byMood.map(m => m.count),
                backgroundColor: '#dca115',
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
    });
}

if (document.getElementById('needWantChart')) {
    new Chart(document.getElementById('needWantChart'), {
        type: 'doughnut',
        data: {
            labels: ['Need', 'Want'],
            datasets: [{
                data: [needWant.need.count, needWant.want.count],
                backgroundColor: ['#059669', '#dca115'],
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

if (impulsiveCategories.length && document.getElementById('impulsiveSpendingChart')) {
    new Chart(document.getElementById('impulsiveSpendingChart'), {
        type: 'doughnut',
        data: {
            labels: impulsiveCategories.map(c => c.category),
            datasets: [{
                data: impulsiveCategories.map(c => c.amount),
                backgroundColor: chartColors,
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
        },
    });
}
</script>
@endpush
@endif
