@extends('portal.layouts.app')

@section('title', 'Diagnostik Keuangan — YFD')
@section('heading', 'Diagnostik Keuangan')

@section('content')
<div class="w-full max-w-3xl mx-auto">
    @include('portal.partials.onboarding-banners')

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 mb-6">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-navy-800 text-2xl shrink-0">health_and_safety</span>
            <div>
                <h2 class="font-bold text-navy-800">Tahap 1 — Diagnostik keuangan</h2>
                <p class="text-sm text-slate-600 mt-1 leading-relaxed">
                    Jawab sesuai kondisi Anda <strong>saat ini</strong>. Hasil otomatis tersimpan ke akun portal Anda
                    dan dipakai untuk insight FTSA serta dashboard.
                </p>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-500 mb-2">
            <span>Langkah <span id="stepLabel">1</span> dari {{ $totalSteps }}</span>
            <span id="progressPct">{{ round(100 / max(1, $totalSteps)) }}%</span>
        </div>
        <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div id="progressBar"
                 class="h-full bg-gradient-to-r from-navy-800 to-navy-600 rounded-full transition-all duration-300"
                 style="width: {{ round(100 / max(1, $totalSteps)) }}%"></div>
        </div>
    </div>

    <form method="post" action="{{ route('portal.diagnostic.store') }}" id="checkupForm">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        @foreach($wizardSteps as $index => $step)
            <div class="checkup-step {{ $index === 0 ? '' : 'hidden' }} bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-4"
                 data-step="{{ $step['step'] }}">
                <div class="bg-navy-800 text-white px-5 py-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gold-400 text-navy-900 font-extrabold text-sm">
                            {{ $step['step'] }}
                        </span>
                        <div class="min-w-0">
                            @if($step['intro'] ?? null)
                                <h2 class="font-bold text-lg leading-snug">{{ $step['intro']['title'] ?? 'Profil' }}</h2>
                                @if(!empty($step['intro']['note']))
                                    <p class="text-white/75 text-sm mt-1 leading-relaxed">{{ $step['intro']['note'] }}</p>
                                @endif
                            @else
                                <h2 class="font-bold text-lg">Langkah {{ $step['step'] }}</h2>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-5 sm:p-6 space-y-8">
                    @foreach($step['questions'] as $q)
                        <fieldset class="space-y-3">
                            <legend class="font-semibold text-navy-800 text-sm">{{ $q['text'] }}</legend>
                            @if(!empty($q['note']))
                                <p class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 leading-relaxed whitespace-pre-line">{{ $q['note'] }}</p>
                            @endif
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($q['options'] as $value => $opt)
                                    @php $label = is_array($opt) ? ($opt['label'] ?? '') : $opt; @endphp
                                    <label class="flex items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-3 cursor-pointer hover:border-navy-500 has-[:checked]:border-navy-600 has-[:checked]:bg-navy-50 transition-colors">
                                        <input type="radio" name="fs[{{ $q['key'] }}]" value="{{ $value }}"
                                               class="text-navy-600 shrink-0"
                                               @checked(old("fs.{$q['key']}") === (string) $value) required>
                                        <span class="text-sm text-slate-800">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error("fs.{$q['key']}")
                                <p class="text-rose-600 text-xs">{{ $message }}</p>
                            @enderror
                        </fieldset>
                    @endforeach
                </div>

                <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/80 flex flex-wrap gap-3">
                    @if($index > 0)
                        <button type="button"
                                class="inline-flex items-center gap-2 border border-navy-800 text-navy-800 hover:bg-white font-bold px-5 py-2.5 rounded-xl text-sm"
                                data-action="back">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            Kembali
                        </button>
                    @endif
                    @if($index < count($wizardSteps) - 1)
                        <button type="button"
                                class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-5 py-2.5 rounded-xl text-sm ml-auto"
                                data-action="next">
                            Lanjut
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </button>
                    @else
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm ml-auto">
                            <span class="material-symbols-outlined text-lg">save</span>
                            Simpan Diagnostik
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const steps = Array.from(document.querySelectorAll('.checkup-step'));
    const total = {{ (int) $totalSteps }};
    const progressBar = document.getElementById('progressBar');
    const stepLabel = document.getElementById('stepLabel');
    const progressPct = document.getElementById('progressPct');
    let current = 0;

    function showPanel(idx) {
        steps.forEach((el, i) => el.classList.toggle('hidden', i !== idx));
        const progress = Math.round(((idx + 1) / total) * 100);
        progressBar.style.width = progress + '%';
        stepLabel.textContent = String(idx + 1);
        progressPct.textContent = progress + '%';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateCurrent() {
        const panel = steps[current];
        const radios = panel.querySelectorAll('input[type="radio"]');
        if (!radios.length) return true;
        const names = [...new Set([...radios].map(r => r.name))];
        return names.every(name => panel.querySelector(`input[name="${name}"]:checked`));
    }

    document.querySelectorAll('[data-action="next"]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!validateCurrent()) {
                alert('Pilih jawaban dulu ya.');
                return;
            }
            if (current < total - 1) {
                current++;
                showPanel(current);
            }
        });
    });

    document.querySelectorAll('[data-action="back"]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (current > 0) {
                current--;
                showPanel(current);
            }
        });
    });

    showPanel(0);
})();
</script>
@endpush
