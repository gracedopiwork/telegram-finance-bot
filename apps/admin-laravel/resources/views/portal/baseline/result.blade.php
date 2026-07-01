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
        @if(($baseline->dominant_archetype ?? '') === 'locked')
            <p class="text-sm text-amber-700 mt-1">FTSA-32 terkunci — upgrade paket premium untuk melihat archetype lengkap.</p>
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

<div class="flex flex-wrap gap-3 mt-6">
    <a href="{{ route('portal.dashboard') }}"
       class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">dashboard</span>
        Buka Dashboard
    </a>
    <a href="{{ route('portal.baseline.create') }}"
       class="inline-flex items-center gap-2 border border-slate-300 hover:border-navy-600 text-navy-800 font-medium px-5 py-2.5 rounded-xl text-sm">
        <span class="material-symbols-outlined text-lg">refresh</span>
        Evaluasi Ulang
    </a>
</div>

<p class="text-xs text-slate-400 mt-4">
    Diisi: {{ $baseline->formatDate() }} ·
    Evaluasi berikutnya: {{ $baseline->formatNextReview() }}
</p>
@endsection
