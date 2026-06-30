@extends('portal.layouts.app')

@section('title', 'Financial Health Check-Up — YFD')
@section('heading', 'Financial Health Check-Up & FTSA-32')

@section('content')
@php
    $fs = $config['financial_stage'] ?? [];
    $likert = $config['likert_labels'] ?? [];
    $ftsaDomains = $config['ftsa_domains'] ?? [];
    $ftsaQuestions = $config['ftsa_questions'] ?? [];
    $currentSection = '';
@endphp

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 mb-6">
        <p class="text-slate-600 text-sm leading-relaxed">
            Jawablah sesuai kondisi Anda <strong>saat ini</strong>. Baseline ini menentukan tahap keuangan dan archetype trauma finansial,
            serta mengatur <em>prescription</em> bucket di dashboard. Diulang setiap <strong>6 bulan</strong>.
        </p>
    </div>

    <form method="post" action="{{ route('portal.baseline.store') }}" class="space-y-8">
        @csrf

        {{-- Financial Stage --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="bg-navy-800 text-white px-5 py-4">
                <h2 class="font-bold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined">account_balance</span>
                    Define Your Financial Stage
                </h2>
                <p class="text-white/70 text-sm mt-1">Profil + skor maksimal 39 poin</p>
            </div>
            <div class="p-5 sm:p-6 space-y-8">
                @foreach($fs['profile'] ?? [] as $q)
                    @if($currentSection !== $q['section'])
                        @php $currentSection = $q['section']; @endphp
                        <h3 class="text-xs font-bold uppercase tracking-wider text-navy-600 border-b pb-2">{{ $q['section'] }}</h3>
                    @endif
                    <fieldset class="space-y-3">
                        <legend class="font-semibold text-navy-800 text-sm">{{ $q['text'] }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($q['options'] as $value => $label)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer hover:border-navy-500 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50">
                                    <input type="radio" name="fs[{{ $q['key'] }}]" value="{{ $value }}"
                                           class="text-navy-600" @checked(old("fs.{$q['key']}") === $value) required>
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error("fs.{$q['key']}")
                            <p class="text-rose-600 text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endforeach

                @php $currentSection = ''; @endphp
                @foreach($fs['scored'] ?? [] as $q)
                    @if($currentSection !== $q['section'])
                        @php $currentSection = $q['section']; @endphp
                        <h3 class="text-xs font-bold uppercase tracking-wider text-navy-600 border-b pb-2 mt-4">{{ $q['section'] }}</h3>
                    @endif
                    <fieldset class="space-y-3">
                        <legend class="font-semibold text-navy-800 text-sm">{{ $q['text'] }}</legend>
                        <div class="grid gap-2">
                            @foreach($q['options'] as $value => $opt)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer hover:border-navy-500 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50">
                                    <input type="radio" name="fs[{{ $q['key'] }}]" value="{{ $value }}"
                                           class="text-navy-600 shrink-0" @checked(old("fs.{$q['key']}") === $value) required>
                                    <span class="text-sm">{{ $opt['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error("fs.{$q['key']}")
                            <p class="text-rose-600 text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endforeach
            </div>
        </div>

        {{-- FTSA-32 --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="bg-navy-800 text-white px-5 py-4">
                <h2 class="font-bold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined">psychology</span>
                    FTSA-32 — Financial Trauma Self-Assessment
                </h2>
                <p class="text-white/70 text-sm mt-1">Skala 1–5 untuk kondisi 12 bulan terakhir</p>
            </div>
            <div class="p-5 sm:p-6 space-y-10">
                @foreach($ftsaDomains as $domainKey => $domain)
                    <div>
                        <h3 class="font-bold text-navy-800 mb-1">{{ $domain['code'] }} — {{ $domain['label'] }}</h3>
                        <p class="text-xs text-slate-500 mb-4">Archetype: {{ $domain['archetype_label'] }}</p>
                        <div class="space-y-6">
                            @foreach($domain['questions'] as $qNum)
                                <fieldset>
                                    <legend class="text-sm text-slate-800 mb-2">
                                        <span class="font-semibold text-navy-600">{{ $qNum }}.</span>
                                        {{ $ftsaQuestions[$qNum] ?? '' }}
                                    </legend>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($likert as $score => $label)
                                            <label class="flex-1 min-w-[4.5rem] text-center rounded-lg border border-slate-200 px-2 py-2 cursor-pointer hover:border-navy-500 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50">
                                                <input type="radio" name="ftsa[{{ $qNum }}]" value="{{ $score }}"
                                                       class="sr-only" @checked((int) old("ftsa.{$qNum}") === $score) required>
                                                <div class="text-lg font-bold text-navy-800">{{ $score }}</div>
                                                <div class="text-[10px] text-slate-500 leading-tight hidden sm:block">{{ $label }}</div>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error("ftsa.{$qNum}")
                                        <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </fieldset>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Snapshot keuangan (Sheet 1A) --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="bg-navy-800 text-white px-5 py-4">
                <h2 class="font-bold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined">inventory_2</span>
                    Snapshot Keuangan (Baseline Data)
                </h2>
                <p class="text-white/70 text-sm mt-1">Opsional — isi perkiraan terbaik Anda saat ini</p>
            </div>
            <div class="p-5 sm:p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-navy-800 mb-1">Current Goal (tujuan finansial utama)</label>
                    <input type="text" name="snapshot[current_goal]" value="{{ old('snapshot.current_goal') }}"
                           class="w-full rounded-lg border-slate-300 text-sm" placeholder="Contoh: Dana darurat 6 bulan + lunasi kartu kredit">
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach([
                        'avg_monthly_income' => 'Rata-rata pendapatan bulanan (Rp)',
                        'emergency_fund' => 'Dana darurat (Rp)',
                        'cash_savings' => 'Cash / tabungan (Rp)',
                        'total_investment' => 'Total investasi (Rp)',
                        'total_asset' => 'Total aset (Rp)',
                        'total_debt' => 'Total utang (Rp)',
                    ] as $field => $label)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                            <input type="number" name="snapshot[{{ $field }}]" min="0" step="1000"
                                   value="{{ old("snapshot.{$field}") }}"
                                   class="w-full rounded-lg border-slate-300 text-sm" placeholder="0">
                        </div>
                    @endforeach
                </div>
                <fieldset>
                    <legend class="text-sm font-semibold text-navy-800 mb-2">Proteksi yang dimiliki</legend>
                    <div class="flex flex-wrap gap-4">
                        @foreach([
                            'has_bpjs' => 'BPJS',
                            'has_health_insurance' => 'Asuransi kesehatan',
                            'has_income_protection' => 'Income protection',
                            'has_life_insurance' => 'Asuransi jiwa',
                        ] as $field => $label)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="snapshot[{{ $field }}]" value="1"
                                       class="rounded border-slate-300 text-navy-600"
                                       @checked(old("snapshot.{$field}"))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 pb-8">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-semibold px-6 py-3 rounded-xl shadow-sm">
                <span class="material-symbols-outlined">save</span>
                Simpan Baseline
            </button>
            @if($hasBaseline ?? false)
                <a href="{{ route('portal.dashboard') }}" class="inline-flex items-center px-4 py-3 text-sm text-slate-600 hover:text-navy-800">
                    Batal
                </a>
            @endif
        </div>
    </form>
</div>
@endsection
