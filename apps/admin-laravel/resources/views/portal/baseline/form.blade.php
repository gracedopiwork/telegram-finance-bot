@extends('portal.layouts.app')

@section('title', 'Baseline Data — YFD')
@section('heading', 'Baseline Data (Wajib Diisi)')

@section('content')
@php
    $fs = $config['financial_stage'] ?? [];
    $likert = $config['likert_labels'] ?? [];
    $ftsaQuestions = $config['ftsa_questions'] ?? [];
    $currentSection = '';
    $ftsaUnlocked = $ftsaUnlocked ?? true;
@endphp

<div class="max-w-3xl">
    @if($hasBaseline ?? false)
        <div class="bg-sky-50 border border-sky-200 rounded-2xl px-5 py-4 mb-6 text-sm text-sky-900">
            Anda mengisi ulang diagnostik. Data sebelumnya akan digantikan setelah disimpan.
        </div>
    @else
        @include('portal.partials.onboarding-checklist', ['compact' => true])
        <div class="h-6"></div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 mb-6">
        <p class="text-slate-600 text-sm leading-relaxed">
            Jawablah sesuai kondisi Anda <strong>saat ini</strong>. Baseline ini menentukan tahap keuangan dan archetype trauma finansial,
            serta mengatur <em>prescription</em> bucket di dashboard. Diulang setiap <strong>6 bulan</strong>.
        </p>
        <div class="mt-3 grid sm:grid-cols-2 gap-3 text-xs">
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                <div class="font-semibold text-slate-700">Last Check-up</div>
                <div class="text-slate-500">Otomatis saat Anda klik Simpan Baseline.</div>
            </div>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                <div class="font-semibold text-slate-700">Next Review</div>
                <div class="text-slate-500">Otomatis +6 bulan (sesuai template baseline Excel).</div>
            </div>
        </div>
    </div>

    <form method="post" action="{{ route('portal.baseline.store') }}" class="space-y-8">
        @csrf

        {{-- Financial Stage --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="bg-navy-800 text-white px-5 py-4">
                <h2 class="font-bold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined">account_balance</span>
                    Baseline Data Keuangan
                </h2>
                <p class="text-white/70 text-sm mt-1">Isi sesuai kondisi saat ini (ada catatan di tiap pilihan agar tidak salah pilih)</p>
            </div>
            <div class="p-5 sm:p-6 space-y-8">
                @foreach($fs['profile'] ?? [] as $q)
                    @if($currentSection !== $q['section'])
                        @php $currentSection = $q['section']; @endphp
                        <h3 class="text-xs font-bold uppercase tracking-wider text-navy-600 border-b pb-2">{{ $q['section'] }}</h3>
                    @endif
                    <fieldset class="space-y-3">
                        <legend class="font-semibold text-navy-800 text-sm">{{ $q['text'] }}</legend>
                        @if(!empty($q['note']))
                            <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">{{ $q['note'] }}</p>
                        @endif
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
                        @if(!empty($q['note']))
                            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">{{ $q['note'] }}</p>
                        @endif
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
                    Kuesioner 1–32
                </h2>
                <p class="text-white/70 text-sm mt-1">Skala 1–5 untuk kondisi 12 bulan terakhir</p>
            </div>
            @if(!$ftsaUnlocked)
                <div class="p-5 sm:p-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
                        FTSA 1–32 saat ini terkunci dan akan aktif setelah upgrade paket premium.
                        Anda tetap bisa simpan baseline data dulu, lalu isi FTSA setelah unlock.
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('checkout.show', ['code' => 'yfd-ftsa-premium']) }}"
                           class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm">
                            <span class="material-symbols-outlined text-lg">lock_open</span>
                            Unlock FTSA Sekarang
                        </a>
                    </div>
                </div>
            @else
            <div class="p-5 sm:p-6 space-y-6">
                @foreach(range(1, 32) as $qNum)
                    <fieldset>
                        <legend class="text-sm text-slate-800 mb-2">
                            <span class="font-semibold text-navy-600">{{ $qNum }}.</span>
                            {{ $ftsaQuestions[$qNum] ?? '' }}
                        </legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach($likert as $score => $label)
                                <label class="flex-1 min-w-[4.5rem] text-center rounded-lg border border-slate-200 px-2 py-2 cursor-pointer hover:border-navy-500 has-[:checked]:border-gold-500 has-[:checked]:bg-gold-50 has-[:checked]:ring-2 has-[:checked]:ring-gold-300">
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
            @endif
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
                    <label class="block text-sm font-semibold text-navy-800 mb-1">Current Goal (tujuan finansial saat ini)</label>
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
