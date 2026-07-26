@extends('portal.layouts.app')

@section('title', match($formMode ?? 'snapshot') {
    'ftsa', 'ftsa_only' => 'FTSA Premium — YFD',
    'ftsa_snapshot' => 'Snapshot Keuangan — YFD',
    default => 'Baseline Data — YFD',
})
@section('heading', match($formMode ?? 'snapshot') {
    'ftsa', 'ftsa_only' => 'Kuesioner FTSA 1–32',
    'ftsa_snapshot' => 'Snapshot Keuangan',
    default => 'Baseline Data',
})

@section('content')
@php
    $formMode = $formMode ?? 'snapshot';
    $fs = $config['financial_stage'] ?? [];
    $likert = $config['likert_labels'] ?? [];
    $ftsaQuestions = $config['ftsa_questions'] ?? [];
    $ftsaUnlocked = $ftsaUnlocked ?? true;
    $isFtsaOnlyPortalUser = $isFtsaOnlyPortalUser ?? false;
    $needsFinancialDiagnostic = $needsFinancialDiagnostic ?? false;
    $showFtsaSection = in_array($formMode, ['ftsa', 'ftsa_only'], true);
    $showSnapshotSection = in_array($formMode, ['snapshot', 'ftsa_snapshot'], true) || ($showInlineSnapshotForm ?? false);
    $showFinancialStageSection = $showSnapshotSection && ($needsFinancialDiagnostic ?? false);
    $existingFs = $existingFs ?? [];
    $existingBaseline = $existingBaseline ?? null;
    $snapshotValue = fn (string $field) => old("snapshot.{$field}", $existingBaseline?->{$field});
    $snapshotChecked = fn (string $field) => (bool) old("snapshot.{$field}", $existingBaseline?->{$field});
    $fsValue = fn (string $key) => old("fs.{$key}", $existingFs[$key] ?? null);
    $formAction = $formMode === 'ftsa' ? route('portal.ftsa.store') : route('portal.baseline.store');
@endphp

<div class="{{ ($isFtsaOnlyPortalUser ?? false) ? 'w-full' : 'w-full max-w-6xl mx-auto' }}">
    @if($hasBaseline ?? false)
        <div class="bg-sky-50 border border-sky-200 rounded-2xl px-5 py-4 mb-6 text-sm text-sky-900">
            @if(($ftsaRetakeLocked ?? false) && ($showFtsaSection ?? false))
                Masa evaluasi FTSA masih berjalan. Bagian FTSA terkunci — Anda hanya dapat memperbarui snapshot keuangan.
            @elseif($ftsaRetakeLocked ?? false)
                Masa evaluasi FTSA masih berjalan. Snapshot keuangan tetap bisa diperbarui; pengisian ulang FTSA tersedia setelah masa evaluasi berakhir.
            @else
                Anda mengisi ulang {{ $isFtsaOnlyPortalUser ? 'data baseline' : 'diagnostik' }}. Data sebelumnya akan digantikan setelah disimpan.
            @endif
        </div>
    @elseif(!$isFtsaOnlyPortalUser)
        @include('portal.partials.onboarding-checklist', ['compact' => true])
        <div class="h-6"></div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 mb-6">
        <p class="text-slate-600 text-sm leading-relaxed">
            @if($showFtsaSection)
                <strong>FTSA (Financial Therapy & Strategic Action)</strong> mengukur pola behavioral finansial Anda
                melalui 32 pertanyaan. Hasilnya menentukan archetype dominan (CHD, RVD, SSD, ESD).
                Evaluasi ulang setiap <strong>12 bulan</strong>.
            @elseif($formMode === 'ftsa_snapshot')
                Isi <strong>snapshot keuangan</strong> Anda: target, pendapatan, tabungan, utang, investasi, aset, dan proteksi.
            @else
                @if($showInlineSnapshotForm ?? false)
                    Diagnostik tahap keuangan sudah tersimpan. Lengkapi <strong>snapshot angka keuangan</strong> di bawah.
                @else
                    Isi <strong>diagnostik tahap keuangan</strong> dan <strong>snapshot angka keuangan</strong> Anda saat ini.
                @endif
                Evaluasi ulang setiap <strong>6 bulan</strong>. Kuesioner FTSA (behavioral) diisi terpisah setelah unlock premium.
            @endif
        </p>
        @if($showSnapshotSection && ($formMode ?? '') !== 'ftsa_snapshot')
        <div class="mt-3 grid sm:grid-cols-2 gap-3 text-xs">
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                <div class="font-semibold text-slate-700">Last Check-up</div>
                <div class="text-slate-500">Otomatis saat Anda klik Simpan Baseline.</div>
            </div>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                <div class="font-semibold text-slate-700">Next Review</div>
                <div class="text-slate-500">Otomatis +6 bulan.</div>
            </div>
        </div>
        @endif
    </div>

    <form method="post" action="{{ $formAction }}" class="space-y-8">
        @csrf

        @if($showFinancialStageSection)
        @php $currentSection = ''; @endphp
        {{-- Financial Stage --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="bg-navy-800 text-white px-5 py-4">
                <h2 class="font-bold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined">account_balance</span>
                    Diagnostik Tahap Keuangan
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
                                           class="text-navy-600" @checked((string) $fsValue($q['key']) === (string) $value) required>
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
                                           class="text-navy-600 shrink-0" @checked((string) $fsValue($q['key']) === (string) $value) required>
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
        @endif

        @if($showFtsaSection)
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
                        FTSA 1–32 saat ini terkunci dan akan aktif setelah upgrade paket premium
                        (<strong>12 bulan evaluasi</strong>).
                        Anda tetap bisa simpan baseline data dulu, lalu isi FTSA setelah unlock.
                    </div>
                    <div class="mt-4">
                        @include('portal.partials.ftsa-unlock-panel', ['variant' => 'inline'])
                    </div>
                </div>
            @else
            <div class="p-5 sm:p-8 space-y-8">
                @if(!empty($ftsaEndsAt))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm">
                        Masa evaluasi FTSA berlaku hingga <strong>{{ $ftsaEndsAt->format('d M Y') }}</strong>.
                        @if($ftsaRetakeLocked ?? false)
                            <span class="block mt-1">Kuesioner terkunci — pengisian ulang tersedia setelah masa evaluasi berakhir.</span>
                        @endif
                    </div>
                @endif
                @if($ftsaRetakeLocked ?? false)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 text-slate-700 px-4 py-4 text-sm">
                        <div class="font-bold text-navy-800">FTSA sudah tersimpan</div>
                        <p class="mt-1">Hasil evaluasi saat ini tetap berlaku hingga {{ $ftsaEndsAt?->format('d M Y') ?? 'masa evaluasi berakhir' }}.
                            Lihat hasil di <a href="{{ route('portal.emotional') }}" class="font-semibold text-navy-800 hover:underline">Hasil FTSA</a>.</p>
                    </div>
                @else
                @foreach(range(1, 32) as $qNum)
                    <fieldset class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 sm:p-5">
                        <legend class="text-sm sm:text-base text-slate-800 mb-4 leading-relaxed">
                            <span class="inline-flex items-center justify-center min-w-[1.75rem] h-7 px-1.5 rounded-lg bg-navy-800 text-white text-xs font-bold mr-2">{{ $qNum }}</span>
                            {{ $ftsaQuestions[$qNum] ?? $ftsaQuestions[(string) $qNum] ?? '' }}
                        </legend>
                        <div class="grid grid-cols-5 gap-2 sm:gap-3">
                            @foreach($likert as $score => $label)
                                <label class="ftsa-likert-btn group flex flex-col items-center justify-center rounded-xl border-2 border-slate-200 bg-white px-1 py-3 sm:py-4 cursor-pointer transition-all duration-150
                                    hover:border-navy-400 hover:bg-slate-50">
                                    <input type="radio" name="ftsa[{{ $qNum }}]" value="{{ $score }}"
                                           class="sr-only" @checked((int) old("ftsa.{$qNum}") === $score) required>
                                    <div class="ftsa-likert-num text-xl sm:text-2xl font-extrabold text-navy-800">{{ $score }}</div>
                                    <div class="ftsa-likert-label mt-1 text-[9px] sm:text-[10px] leading-tight text-center text-slate-500 font-medium px-0.5">{{ $label }}</div>
                                </label>
                            @endforeach
                        </div>
                        @error("ftsa.{$qNum}")
                            <p class="text-rose-600 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endforeach
                @endif
            </div>
            @endif
        </div>
        @endif

        @if($showSnapshotSection)
        @if($showInlineSnapshotForm ?? false)
        <div class="rounded-xl border border-gold-400/50 bg-gold-50 px-4 py-3 mb-4 text-sm text-amber-900">
            <strong>Diagnostik sudah tersimpan</strong> ({{ $existingBaseline?->stage_label ?? '—' }}).
            Isi snapshot angka keuangan di bawah lalu klik Simpan.
        </div>
        @endif
        @php
            $snapshotFields = [
                'avg_monthly_income' => 'Rata-rata pendapatan bulanan (Rp)',
                'emergency_fund' => 'Dana darurat (Rp)',
                'cash_savings' => 'Cash / tabungan (Rp)',
                'total_investment' => 'Total investasi (Rp)',
                'total_asset' => 'Total aset (Rp)',
                'total_debt' => 'Total utang (Rp)',
            ];
        @endphp
        {{-- Snapshot keuangan --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="bg-navy-800 text-white px-5 py-4">
                <h2 class="font-bold text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined">inventory_2</span>
                    {{ ($formMode ?? '') === 'ftsa_snapshot' ? 'Snapshot Keuangan' : 'Snapshot Keuangan (Baseline Data)' }}
                </h2>
                <p class="text-white/70 text-sm mt-1">
                    @if(($formMode ?? '') === 'ftsa_snapshot')
                        Perkiraan terbaik Anda saat ini — target, angka keuangan, dan proteksi
                    @else
                        Opsional — isi perkiraan terbaik Anda saat ini
                    @endif
                </p>
            </div>
            <div class="p-5 sm:p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-navy-800 mb-1">Current Goal (tujuan finansial saat ini)</label>
                    <input type="text" name="snapshot[current_goal]" value="{{ $snapshotValue('current_goal') }}"
                           class="w-full rounded-lg border-slate-300 text-sm" placeholder="Contoh: Dana darurat 6 bulan + lunasi kartu kredit">
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach($snapshotFields as $field => $label)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                            <input type="number" name="snapshot[{{ $field }}]" min="0" step="1000"
                                   value="{{ $snapshotValue($field) }}"
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
                                       @checked($snapshotChecked($field))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                @include('portal.partials.baseline-asset-protection-fields', ['existingBaseline' => $existingBaseline ?? null])
            </div>
        </div>
        @endif

        <div class="flex flex-wrap gap-3 pb-8">
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-semibold px-6 py-3 rounded-xl shadow-sm">
                <span class="material-symbols-outlined">save</span>
                @if($showFtsaSection)
                    Simpan FTSA
                @elseif($formMode === 'ftsa_snapshot')
                    Simpan Snapshot
                @else
                    Simpan Baseline
                @endif
            </button>
            @if($hasBaseline ?? false)
                <a href="{{ route(($isFtsaOnlyPortalUser ?? false) ? 'portal.emotional' : 'portal.dashboard') }}" class="inline-flex items-center px-4 py-3 text-sm text-slate-600 hover:text-navy-800">
                    Batal
                </a>
            @endif
        </div>
    </form>
</div>

@push('scripts')
<style>
    .ftsa-likert-btn:has(input:checked) {
        background-color: {{ config('yfd_brand.navy') }};
        border-color: {{ config('yfd_brand.gold') }};
        box-shadow: 0 10px 15px -3px rgb(13 43 78 / 0.25);
        transform: scale(1.03);
    }
    .ftsa-likert-btn:has(input:checked) .ftsa-likert-num {
        color: {{ config('yfd_brand.gold') }};
    }
    .ftsa-likert-btn:has(input:checked) .ftsa-likert-label {
        color: #fff;
    }
</style>
@endpush
@endsection
