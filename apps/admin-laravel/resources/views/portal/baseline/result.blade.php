@extends('portal.layouts.app')

@section('title', 'Baseline — YFD')
@section('heading', 'Baseline')

@section('content')
@php
    $domainScores = [
        'chd' => ['score' => (int) ($baseline->ftsa_chd ?? 0), 'level' => $baseline->chd_level, 'meta' => is_array($domains['chd'] ?? null) ? $domains['chd'] : []],
        'rvd' => ['score' => (int) ($baseline->ftsa_rvd ?? 0), 'level' => $baseline->rvd_level, 'meta' => is_array($domains['rvd'] ?? null) ? $domains['rvd'] : []],
        'ssd' => ['score' => (int) ($baseline->ftsa_ssd ?? 0), 'level' => $baseline->ssd_level, 'meta' => is_array($domains['ssd'] ?? null) ? $domains['ssd'] : []],
        'esd' => ['score' => (int) ($baseline->ftsa_esd ?? 0), 'level' => $baseline->esd_level, 'meta' => is_array($domains['esd'] ?? null) ? $domains['esd'] : []],
    ];
    $prescriptionRows = is_array($prescription ?? null) ? $prescription : [];
    $dominantArchetype = (string) ($baseline->dominant_archetype ?? '');
    $dominantMeta = collect($domainScores)->first(
        fn ($d) => $dominantArchetype !== '' && $dominantArchetype !== 'locked' && $dominantArchetype === ($d['meta']['archetype'] ?? '')
    );
    $dominantSummary = $dominantMeta['meta']['archetype_summary'] ?? null;
    $fmt = fn (int $n) => \App\Support\RupiahFormat::format($n);
    $hasFtsa = ($baseline->dominant_archetype ?? '') !== 'locked'
        && app(\App\Services\FtsaAnswerSummaryService::class)->hasCompletedFtsa($baseline);
    $stageLastCheckup = $baseline->formatDate('d M Y');
    $stageNextCheckup = $baseline->formatNextReview('d M Y');
    $ftsaLastCheckup = $hasFtsa ? $baseline->formatDate('d M Y') : '—';
    $ftsaNextCheckup = ! empty($ftsaEndsAt)
        ? $ftsaEndsAt->format('d M Y')
        : (($ftsaRetakeAvailableAt ?? null)?->format('d M Y') ?? '—');
    $hasSnapshot = trim((string) ($baseline->current_goal ?? '')) !== ''
        || collect(['avg_monthly_income', 'emergency_fund', 'cash_savings', 'total_investment', 'total_asset', 'total_debt'])
            ->contains(fn ($f) => $baseline->{$f} !== null && (int) $baseline->{$f} > 0)
        || $baseline->has_bpjs || $baseline->has_health_insurance
        || $baseline->has_income_protection || $baseline->has_life_insurance;
    $hasGoal = trim((string) ($baseline->current_goal ?? '')) !== '';
@endphp

@if($reviewDue)
    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 flex flex-wrap items-center justify-between gap-3 mb-6">
        <span>Baseline terakhir: {{ $baseline->formatDate('d M Y') }}. Sudah waktunya evaluasi ulang (6 bulan).</span>
        <a href="{{ route('portal.baseline.create') }}" class="font-semibold text-amber-800 hover:underline">Isi ulang sekarang →</a>
    </div>
@endif

{{-- Grid 2 kolom: kiri = Stage + Penjelasan FTSA · kanan = Archetype + Prescription --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
    <div class="space-y-5">
        {{-- Financial Stage --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col">
            <h3 class="font-bold text-navy-800 text-lg mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined">stairs</span> Financial Stage
            </h3>
            <div class="grid grid-cols-2 gap-3 text-sm mb-4 pb-4 border-b border-slate-100">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Last check up</div>
                    <div class="font-semibold text-navy-800 mt-0.5">{{ $stageLastCheckup }}</div>
                </div>
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Next check up</div>
                    <div class="font-semibold text-navy-800 mt-0.5">{{ $stageNextCheckup }}</div>
                </div>
            </div>
            <div class="text-4xl mb-1">{{ $stageMeta['emoji'] ?? '' }}</div>
            <div class="text-2xl font-extrabold text-navy-800">{{ $baseline->stage_label }}</div>
            <div class="text-sm text-slate-500 mt-1">{{ $stageMeta['phase'] ?? '' }} · Skor {{ $baseline->financial_stage_score }}/39</div>
            <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $stageMeta['diagnosis'] ?? '' }}</p>
            @include('portal.partials.financial-stage-guidance', ['stageGuidance' => $stageGuidance ?? []])
        </div>

        @include('portal.partials.ftsa-domain-explanation', ['domainScores' => $domainScores])
    </div>

    <div class="space-y-5">
        {{-- Archetype --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col">
        <h3 class="font-bold text-navy-800 text-lg mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined">psychology</span> Archetype
        </h3>
        <div class="grid grid-cols-2 gap-3 text-sm mb-4 pb-4 border-b border-slate-100">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Last check up</div>
                <div class="font-semibold text-navy-800 mt-0.5">{{ $ftsaLastCheckup }}</div>
            </div>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Next check up</div>
                <div class="font-semibold text-navy-800 mt-0.5">{{ $ftsaNextCheckup }}</div>
            </div>
        </div>
        <div class="text-2xl font-extrabold text-navy-800">{{ $baseline->dominant_archetype_label ?? 'Belum dinilai' }}</div>
        @if(($baseline->dominant_archetype ?? '') === 'locked' && !($ftsaUnlocked ?? false))
            <p class="text-sm text-amber-700 mt-2">FTSA-32 terkunci — upgrade paket premium untuk melihat archetype lengkap.</p>
            @include('portal.partials.ftsa-unlock-panel', ['variant' => 'inline'])
        @elseif(($baseline->dominant_archetype ?? '') === 'locked' && ($ftsaUnlocked ?? false))
            <p class="text-sm text-slate-600 mt-2">FTSA Premium aktif. Isi kuesioner 1–32 untuk melihat archetype.</p>
            <a href="{{ route('portal.ftsa.create') }}"
               class="inline-flex items-center gap-2 mt-3 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm w-fit">
                <span class="material-symbols-outlined text-lg">edit_note</span>
                Isi FTSA Sekarang
            </a>
        @else
            <p class="text-sm text-slate-500 mt-1">Domain dominan pada FTSA-32</p>
            @if($dominantSummary)
                <p class="text-sm text-slate-600 mt-2 leading-relaxed">{{ $dominantSummary }}</p>
            @endif
            <div class="mt-4 space-y-2 flex-1">
                @foreach($domainScores as $key => $d)
                    @php
                        $isDominant = $baseline->dominant_archetype === ($d['meta']['archetype'] ?? '');
                        $pct = round(((int) $d['score'] / 40) * 100);
                    @endphp
                    <div class="{{ $isDominant ? 'ring-2 ring-gold-400 rounded-lg p-2 -mx-2' : '' }}">
                        <div class="flex justify-between text-xs mb-0.5">
                            <span class="font-medium text-navy-800">{{ $d['meta']['code'] ?? strtoupper($key) }}</span>
                            <span class="text-slate-500">{{ $d['score'] }}/40</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-navy-500 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        </div>

        {{-- Prescription budget --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <h3 class="font-bold text-navy-800 text-lg mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined">medication</span> Prescription Budget
            </h3>
            <p class="text-sm text-slate-600 mb-4">Target alokasi bucket untuk tahap <strong>{{ $baseline->stage_label }}</strong>.</p>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="pb-2 font-semibold">Bucket</th>
                        <th class="pb-2 font-semibold text-right">Target</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescriptionRows as $bucket => $pct)
                        <tr class="border-b border-slate-50">
                            <td class="py-2.5 text-navy-800 font-medium">{{ $bucket }}</td>
                            <td class="py-2.5 text-right font-bold text-navy-800">{{ $pct }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Snapshot keuangan --}}
@if($hasSnapshot)
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6">
    <div class="bg-slate-50 px-5 py-4 border-b flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-bold text-navy-800 flex items-center gap-2 text-lg">
            <span class="material-symbols-outlined">inventory_2</span>
            Snapshot Keuangan
        </h3>
        <a href="{{ route('portal.baseline.create') }}"
           class="text-xs font-semibold text-navy-800 hover:underline">Perbarui snapshot →</a>
    </div>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
        @foreach([
            'avg_monthly_income' => 'Pendapatan/bulan',
            'emergency_fund' => 'Dana darurat',
            'cash_savings' => 'Tabungan',
            'total_investment' => 'Investasi',
            'total_asset' => 'Total aset',
            'total_debt' => 'Total utang',
        ] as $field => $label)
            @if($baseline->{$field})
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                    <div class="font-bold text-navy-800 text-base">{{ $fmt((int) $baseline->{$field}) }}</div>
                </div>
            @endif
        @endforeach
        <div class="rounded-xl bg-slate-50 p-3 sm:col-span-2 lg:col-span-3">
            <div class="text-xs text-slate-500 mb-2">Proteksi</div>
            <div class="flex flex-wrap gap-1.5">
                @if($baseline->has_bpjs)<span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold">BPJS</span>@endif
                @if($baseline->has_health_insurance)<span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold">Asuransi kesehatan</span>@endif
                @if($baseline->has_income_protection)<span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold">Income protection</span>@endif
                @if($baseline->has_life_insurance)<span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold">Asuransi jiwa</span>@endif
                @if(! $baseline->has_bpjs && ! $baseline->has_health_insurance && ! $baseline->has_income_protection && ! $baseline->has_life_insurance)
                    <span class="text-xs text-slate-500">Belum ada proteksi tercatat</span>
                @endif
            </div>
        </div>
    </div>
</div>
@else
<div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 flex flex-wrap items-center justify-between gap-3 mb-6">
    <span>Snapshot keuangan belum lengkap.</span>
    <a href="{{ route('portal.baseline.create') }}" class="font-semibold hover:underline">Lengkapi snapshot →</a>
</div>
@endif

{{-- Goal --}}
<div class="bg-white rounded-2xl border border-gold-200 shadow-sm overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-gold-50 to-amber-50 px-5 py-4 border-b border-gold-200">
        <h3 class="font-bold text-navy-800 text-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-700">flag</span>
            Goal
        </h3>
    </div>
    <div class="p-5 sm:p-6 min-h-[120px]">
        @if($hasGoal)
            <p class="text-base sm:text-lg text-navy-800 leading-relaxed font-medium">
                {{ \App\Support\RupiahFormat::formatText($baseline->current_goal) }}
            </p>
        @else
            <p class="text-sm text-slate-500">Belum ada goal finansial. Tuliskan target jangka pendek atau menengah Anda saat mengisi snapshot baseline.</p>
            <a href="{{ route('portal.baseline.create') }}"
               class="inline-flex items-center gap-2 mt-4 text-sm font-semibold text-navy-800 hover:underline">
                <span class="material-symbols-outlined text-base">edit</span>
                Tambahkan goal
            </a>
        @endif
    </div>
</div>

{{-- Actions --}}
<div class="flex flex-wrap gap-3">
    <a href="{{ ($isFtsaOnlyPortalUser ?? false) ? route('portal.emotional') : route('portal.dashboard') }}"
       class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">{{ ($isFtsaOnlyPortalUser ?? false) ? 'psychology' : 'dashboard' }}</span>
        {{ ($isFtsaOnlyPortalUser ?? false) ? 'Lihat Hasil FTSA' : 'Buka Dashboard' }}
    </a>
    <a href="{{ route('portal.baseline.create') }}"
       class="inline-flex items-center gap-2 border border-slate-300 hover:border-navy-600 text-navy-800 font-medium px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">refresh</span>
        Evaluasi Ulang
    </a>
    @if(($baseline->dominant_archetype ?? '') !== 'locked' && ($ftsaUnlocked ?? false))
        @if($ftsaRetakeLocked ?? false)
            <span class="inline-flex items-center gap-2 border border-slate-200 bg-slate-50 text-slate-500 font-medium px-5 py-2.5 rounded-xl text-sm cursor-not-allowed"
                  title="Evaluasi FTSA tersedia setelah {{ $ftsaRetakeAvailableAt?->format('d M Y') ?? 'masa evaluasi berakhir' }}">
                <span class="material-symbols-outlined text-lg">lock</span>
                FTSA terkunci hingga {{ $ftsaRetakeAvailableAt?->format('d M Y') ?? '—' }}
            </span>
        @else
            <a href="{{ route('portal.ftsa.create') }}"
               class="inline-flex items-center gap-2 border border-gold-400 text-navy-800 font-medium px-5 py-2.5 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">psychology</span>
                Evaluasi FTSA
            </a>
        @endif
    @endif
</div>
@endsection
