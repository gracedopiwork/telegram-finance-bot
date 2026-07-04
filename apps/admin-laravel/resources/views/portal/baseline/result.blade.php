@extends('portal.layouts.app')

@section('title', 'Hasil Baseline — YFD')
@section('heading', 'Hasil Baseline Data')

@section('content')
@php
    $domainScores = [
        'chd' => ['score' => (int) ($baseline->ftsa_chd ?? 0), 'level' => $baseline->chd_level, 'meta' => is_array($domains['chd'] ?? null) ? $domains['chd'] : []],
        'rvd' => ['score' => (int) ($baseline->ftsa_rvd ?? 0), 'level' => $baseline->rvd_level, 'meta' => is_array($domains['rvd'] ?? null) ? $domains['rvd'] : []],
        'ssd' => ['score' => (int) ($baseline->ftsa_ssd ?? 0), 'level' => $baseline->ssd_level, 'meta' => is_array($domains['ssd'] ?? null) ? $domains['ssd'] : []],
        'esd' => ['score' => (int) ($baseline->ftsa_esd ?? 0), 'level' => $baseline->esd_level, 'meta' => is_array($domains['esd'] ?? null) ? $domains['esd'] : []],
    ];
    $prescriptionRows = is_array($prescription ?? null) ? $prescription : [];
@endphp

@if($reviewDue)
    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 flex flex-wrap items-center justify-between gap-3">
        <span>Baseline terakhir: {{ $baseline->formatDate('d M Y') }}. Sudah waktunya evaluasi ulang (6 bulan).</span>
        <a href="{{ route('portal.baseline.create') }}" class="font-semibold text-amber-800 hover:underline">Isi ulang sekarang →</a>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Financial Stage --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 text-lg mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">stairs</span> Financial Stage
        </h3>
        <div class="text-4xl mb-2">{{ $stageMeta['emoji'] ?? '' }}</div>
        <div class="text-2xl font-extrabold text-navy-800">{{ $baseline->stage_label }}</div>
        <div class="text-sm text-slate-500 mt-1">{{ $stageMeta['phase'] ?? '' }} · Skor {{ $baseline->financial_stage_score }}/39</div>
        <p class="mt-3 text-sm text-slate-600">{{ $stageMeta['diagnosis'] ?? '' }}</p>
        <div class="mt-5 pt-4 border-t">
            <div class="text-xs font-bold uppercase text-slate-500 mb-2">Prescription Bucket</div>
            <table class="w-full text-sm">
                @foreach($prescriptionRows as $bucket => $pct)
                    <tr class="border-b border-slate-50">
                        <td class="py-2 text-navy-800">{{ $bucket }}</td>
                        <td class="py-2 text-right font-semibold">{{ $pct }}%</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    {{-- Dominant Archetype --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <h3 class="font-bold text-navy-800 text-lg mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">psychology</span> Dominant Archetype
        </h3>
        <div class="text-2xl font-extrabold text-navy-800">{{ $baseline->dominant_archetype_label ?? 'Belum dinilai' }}</div>
        @if(($baseline->dominant_archetype ?? '') === 'locked' && !($ftsaUnlocked ?? false))
            <p class="text-sm text-amber-700 mt-1">FTSA-32 terkunci — upgrade paket premium untuk melihat archetype lengkap.</p>
            @include('portal.partials.ftsa-unlock-panel', ['variant' => 'inline'])
        @elseif(($baseline->dominant_archetype ?? '') === 'locked' && ($ftsaUnlocked ?? false))
            <p class="text-sm text-slate-600 mt-1">FTSA Premium sudah aktif. Isi kuesioner 1–32 untuk melihat archetype lengkap.</p>
            <a href="{{ route('portal.ftsa.create') }}"
               class="inline-flex items-center gap-2 mt-3 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
                <span class="material-symbols-outlined text-lg">edit_note</span>
                Isi FTSA Sekarang
            </a>
        @else
            <p class="text-sm text-slate-500 mt-1">Domain dengan skor tertinggi pada FTSA-32</p>
        @endif
        <div class="mt-5 space-y-3">
            @foreach($domainScores as $key => $d)
                @php
                    $isDominant = ($baseline->dominant_archetype ?? '') !== 'locked'
                        && $baseline->dominant_archetype === ($d['meta']['archetype'] ?? '');
                    $pct = round(((int) $d['score'] / 40) * 100);
                @endphp
                <div class="{{ $isDominant ? 'ring-2 ring-gold-400 rounded-lg p-2 -mx-2' : '' }}">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-navy-800">{{ $d['meta']['code'] ?? strtoupper($key) }}</span>
                        <span class="text-slate-500">{{ $d['score'] }}/40{{ $d['level'] ? ' · '.$d['level'] : '' }}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-navy-500 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@php
    $hasSnapshot = trim((string) ($baseline->current_goal ?? '')) !== ''
        || collect(['avg_monthly_income', 'emergency_fund', 'cash_savings', 'total_investment', 'total_asset', 'total_debt'])
            ->contains(fn ($f) => $baseline->{$f} !== null && (int) $baseline->{$f} > 0)
        || $baseline->has_bpjs || $baseline->has_health_insurance
        || $baseline->has_income_protection || $baseline->has_life_insurance;
    $fmt = fn (int $n) => \App\Support\RupiahFormat::format($n);
@endphp

@if($hasSnapshot)
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mt-6">
    <div class="bg-slate-50 px-5 py-4 border-b">
        <h3 class="font-bold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined">inventory_2</span>
            Snapshot Keuangan
        </h3>
    </div>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        @if($baseline->current_goal)
            <div class="sm:col-span-2 lg:col-span-4 rounded-xl bg-gold-50 border border-gold-200 p-3">
                <div class="text-xs font-bold text-amber-800 uppercase">Current Goal</div>
                <div class="font-medium text-navy-800 mt-1">{{ \App\Support\RupiahFormat::formatText($baseline->current_goal) }}</div>
            </div>
        @endif
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
                    <div class="font-bold text-navy-800">{{ $fmt((int) $baseline->{$field}) }}</div>
                </div>
            @endif
        @endforeach
        <div class="rounded-xl bg-slate-50 p-3">
            <div class="text-xs text-slate-500 mb-1">Proteksi</div>
            <div class="flex flex-wrap gap-1">
                @if($baseline->has_bpjs)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">BPJS</span>@endif
                @if($baseline->has_health_insurance)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Kesehatan</span>@endif
                @if($baseline->has_income_protection)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Income</span>@endif
                @if($baseline->has_life_insurance)<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Jiwa</span>@endif
            </div>
        </div>
    </div>
</div>
@endif

<div class="flex flex-wrap gap-3 mt-6">
    <a href="{{ ($isFtsaOnlyPortalUser ?? false) ? route('portal.emotional') : route('portal.dashboard') }}"
       class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">{{ ($isFtsaOnlyPortalUser ?? false) ? 'psychology' : 'dashboard' }}</span>
        {{ ($isFtsaOnlyPortalUser ?? false) ? 'Lihat Hasil FTSA' : 'Buka Dashboard' }}
    </a>
    <a href="{{ route('portal.baseline.create', ['section' => 'snapshot']) }}"
       class="inline-flex items-center gap-2 border border-slate-300 hover:border-navy-600 text-navy-800 font-medium px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">refresh</span>
        Perbarui Snapshot
    </a>
    @if(($baseline->dominant_archetype ?? '') !== 'locked')
    <a href="{{ route('portal.ftsa.create') }}"
       class="inline-flex items-center gap-2 border border-gold-400 text-navy-800 font-medium px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">psychology</span>
        Evaluasi FTSA
    </a>
    @endif
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
    <div class="font-bold text-navy-800 mb-2">Tentang FTSA</div>
    <p>FTSA (Financial Therapy & Strategic Action) mengukur pola behavioral finansial lewat 32 pertanyaan.
        Hasilnya menentukan archetype dominan dan membantu interpretasi dashboard behavioral.</p>
    <p class="mt-2 text-xs text-slate-500">
        Baseline keuangan &amp; financial stage: evaluasi setiap <strong>6 bulan</strong>.
        FTSA: evaluasi setiap <strong>12 bulan</strong> setelah unlock premium.
    </p>
</div>

<p class="text-xs text-slate-400 mt-4">
    Diisi: {{ $baseline->formatDate() }} ·
    Evaluasi berikutnya: {{ $baseline->formatNextReview() }}
</p>
@endsection
