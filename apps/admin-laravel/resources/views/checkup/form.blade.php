@extends('Companyprofile.layouts.main')

@section('title', 'Financial Health Check-Up — Your Financial Doctor')
@section('description', 'Cek tahap kesehatan finansial Anda secara gratis. Tanpa login — cukup email untuk menyimpan hasil.')

@push('head')
<style>
    .checkup-wizard { background: #B8E8E0; min-height: 70vh; }
    .checkup-step-badge {
        width: 2.25rem; height: 2.25rem;
        background: #0c2240; color: #fff;
        font-weight: 800; border-radius: 0.5rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .checkup-option {
        display: flex; align-items: center; gap: 0.75rem;
        background: rgba(255,255,255,0.35);
        border: 2px solid rgba(12,34,64,0.15);
        border-radius: 0.75rem;
        padding: 0.85rem 1rem;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .checkup-option:has(input:checked) {
        border-color: #0c2240;
        background: rgba(255,255,255,0.65);
        box-shadow: 0 0 0 1px #0c2240;
    }
    .checkup-option-letter {
        width: 1.75rem; height: 1.75rem;
        border: 2px solid rgba(12,34,64,0.35);
        border-radius: 0.35rem;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.8rem; flex-shrink: 0;
        background: rgba(255,255,255,0.5);
    }
    .checkup-option input { position: absolute; opacity: 0; pointer-events: none; }
    .checkup-ok-btn {
        background: #3B9BFF; color: #0c2240; font-weight: 800;
        border-radius: 0.5rem; padding: 0.5rem 1.75rem;
        border: none; cursor: pointer;
    }
    .checkup-ok-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .checkup-progress { height: 4px; background: rgba(12,34,64,0.12); border-radius: 999px; overflow: hidden; }
    .checkup-progress-bar { height: 100%; background: #0c2240; transition: width .25s; }
</style>
@endpush

@section('content')
<section class="py-8 md:py-12">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-2xl mx-auto">
            @if(session('error'))
                <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif
            @if(session('warning'))
                <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">{{ session('warning') }}</div>
            @endif

            <div class="checkup-progress mb-4">
                <div class="checkup-progress-bar" id="progressBar" style="width: {{ round(100 / max(1, $totalSteps)) }}%"></div>
            </div>

            <form method="post" action="{{ route('checkup.store') }}" id="checkupForm">
                @csrf

                {{-- Email di langkah terakhir --}}
                <div id="emailStep" class="hidden checkup-wizard rounded-2xl p-6 sm:p-8">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="checkup-step-badge">{{ $totalSteps + 1 }}</span>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Simpan hasil check-up</h2>
                            <p class="text-sm text-slate-700 mt-1">Masukkan email agar hasil bisa dihubungkan saat Anda beli YFD First Aid.</p>
                        </div>
                    </div>
                    <input type="email" name="email" id="checkupEmail" required
                           value="{{ old('email', $prefillEmail ?? '') }}"
                           class="w-full rounded-lg border-slate-400/50 bg-white/60 px-4 py-3 text-sm"
                           placeholder="nama@email.com">
                    @error('email')<p class="text-rose-700 text-xs mt-2">{{ $message }}</p>@enderror
                    <div class="mt-6 flex gap-3">
                        <button type="button" class="checkup-ok-btn" data-action="back">Kembali</button>
                        <button type="submit" class="checkup-ok-btn">Lihat Hasil</button>
                    </div>
                </div>

                @foreach($wizardSteps as $index => $step)
                    <div class="checkup-step {{ $index === 0 ? '' : 'hidden' }} checkup-wizard rounded-2xl p-6 sm:p-8"
                         data-step="{{ $step['step'] }}">
                        <div class="flex items-start gap-3 mb-2">
                            <span class="checkup-step-badge">{{ $step['step'] }}</span>
                            <div class="flex-1">
                                @if($step['intro'] ?? null)
                                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 leading-snug">
                                        {{ $step['intro']['title'] ?? 'Profil' }}*
                                    </h2>
                                    @if(!empty($step['intro']['note']))
                                        <p class="text-sm text-slate-700 mt-2 leading-relaxed">{{ $step['intro']['note'] }}</p>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="space-y-8 mt-6">
                            @foreach($step['questions'] as $q)
                                <fieldset class="space-y-3" data-question-key="{{ $q['key'] }}">
                                    <legend class="text-base sm:text-lg font-bold text-slate-900">{{ $q['text'] }}*</legend>
                                    @if(!empty($q['note']))
                                        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $q['note'] }}</p>
                                    @endif
                                    <div class="space-y-2.5">
                                        @php $letters = range('A', 'Z'); @endphp
                                        @foreach($q['options'] as $value => $opt)
                                            @php
                                                $label = is_array($opt) ? ($opt['label'] ?? '') : $opt;
                                                $letter = $letters[$loop->index] ?? '?';
                                            @endphp
                                            <label class="checkup-option relative">
                                                <input type="radio" name="fs[{{ $q['key'] }}]" value="{{ $value }}"
                                                       @checked(old("fs.{$q['key']}") === (string) $value) required>
                                                <span class="checkup-option-letter">{{ $letter }}</span>
                                                <span class="text-sm sm:text-base font-medium text-slate-900">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error("fs.{$q['key']}")
                                        <p class="text-rose-700 text-xs">{{ $message }}</p>
                                    @enderror
                                </fieldset>
                            @endforeach
                        </div>

                        <div class="mt-8 flex gap-3">
                            @if($index > 0)
                                <button type="button" class="checkup-ok-btn" data-action="back">Kembali</button>
                            @endif
                            <button type="button" class="checkup-ok-btn" data-action="next">OK</button>
                        </div>
                    </div>
                @endforeach
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const steps = Array.from(document.querySelectorAll('.checkup-step'));
    const emailStep = document.getElementById('emailStep');
    const allPanels = [...steps, emailStep];
    const total = {{ (int) $totalSteps }};
    let current = 0;

    function showPanel(idx) {
        allPanels.forEach((el, i) => el.classList.toggle('hidden', i !== idx));
        const progress = idx >= total ? 100 : Math.round(((idx + 1) / (total + 1)) * 100);
        document.getElementById('progressBar').style.width = progress + '%';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateCurrent() {
        const panel = allPanels[current];
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
            } else {
                current = total;
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
