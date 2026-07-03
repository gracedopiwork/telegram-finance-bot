@extends('portal.layouts.app')

@section('title', 'Financial Health — Premium')
@section('heading', 'Financial Care Plan (Premium)')

@section('content')
<div class="max-w-2xl mx-auto text-center py-12">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 mb-6 text-left">
        @if($ftsaUnlocked ?? false)
            <div class="text-sm text-emerald-800 font-bold">FTSA Premium sudah aktif</div>
            <div class="text-sm text-emerald-700 mt-1">Kuesioner FTSA 1-32 dan personalisasi rekomendasi sudah terbuka di Baseline & Behavioral Dashboard.</div>
            @if(!empty($ftsaEndsAt))
                <div class="text-xs text-emerald-700/80 mt-2">Masa evaluasi berlaku hingga <strong>{{ $ftsaEndsAt->format('d M Y') }}</strong> (12 bulan).</div>
            @endif
        @else
            @include('portal.partials.ftsa-unlock-panel', ['variant' => 'block'])
        @endif
    </div>

    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gold-400 to-gold-500 flex items-center justify-center mx-auto mb-6 shadow-lg">
        <span class="material-symbols-outlined text-4xl text-navy-800">monitor_heart</span>
    </div>
    <h2 class="text-2xl font-extrabold text-navy-800">Sheet 4 — Financial Care Plan</h2>
    <p class="text-slate-600 mt-3 leading-relaxed">
        Modul premium untuk rencana perawatan finansial personal, goal planning terstruktur,
        dan integrasi konsultasi FMR 01 / FMR 02 dengan penasihat YFD.
    </p>
    <div class="mt-8 grid sm:grid-cols-2 gap-4 text-left text-sm">
        <div class="rounded-xl border bg-white p-4">
            <div class="font-bold text-navy-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-gold-500">flag</span> Goal Planning
            </div>
            <p class="text-slate-600 mt-2">Target finansial terukur dengan milestone & review berkala.</p>
        </div>
        <div class="rounded-xl border bg-white p-4">
            <div class="font-bold text-navy-800 flex items-center gap-2">
                <span class="material-symbols-outlined text-gold-500">medical_services</span> Care Plan
            </div>
            <p class="text-slate-600 mt-2">Resep finansial personal dari penasihat bersertifikat YFD.</p>
        </div>
    </div>
    <p class="mt-8 text-sm text-slate-500">
        Fitur ini akan tersedia pada fase berikutnya. Untuk saat ini, gunakan
        <a href="{{ route('portal.dashboard') }}" class="text-navy-800 font-semibold hover:underline">Dashboard Keuangan</a>
        dan <a href="{{ route('portal.emotional') }}" class="text-navy-800 font-semibold hover:underline">Emotional Scan</a>.
    </p>
</div>
@endsection
