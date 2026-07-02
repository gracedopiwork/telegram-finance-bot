@extends('portal.layouts.app')

@section('title', 'Diagnostik Keuangan — YFD')
@section('heading', 'Diagnostik Keuangan')

@push('head')
<style>
    .checkup-wizard { background: #B8E8E0; border-radius: 1rem; }
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
    }
    .checkup-option:has(input:checked) {
        border-color: #0c2240;
        background: rgba(255,255,255,0.65);
    }
    .checkup-option-letter {
        width: 1.75rem; height: 1.75rem;
        border: 2px solid rgba(12,34,64,0.35);
        border-radius: 0.35rem;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.8rem; flex-shrink: 0;
    }
    .checkup-option input { position: absolute; opacity: 0; pointer-events: none; }
    .checkup-ok-btn {
        background: #d4a843; color: #0c2240; font-weight: 800;
        border-radius: 0.75rem; padding: 0.65rem 1.5rem;
        border: none; cursor: pointer;
    }
    .checkup-progress { height: 4px; background: rgba(12,34,64,0.12); border-radius: 999px; overflow: hidden; }
    .checkup-progress-bar { height: 100%; background: #0c2240; transition: width .25s; }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 mb-6 text-sm text-slate-600">
        Jawab sesuai kondisi Anda saat ini. Hasil otomatis tersimpan ke akun portal Anda.
    </div>

    <div class="checkup-progress mb-4">
        <div class="checkup-progress-bar" id="progressBar" style="width: {{ round(100 / max(1, $totalSteps)) }}%"></div>
    </div>

    <form method="post" action="{{ route('portal.diagnostic.store') }}" id="checkupForm">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        @foreach($wizardSteps as $index => $step)
            <div class="checkup-step {{ $index === 0 ? '' : 'hidden' }} checkup-wizard p-6 sm:p-8 mb-4"
                 data-step="{{ $step['step'] }}">
                <div class="flex items-start gap-3 mb-2">
                    <span class="checkup-step-badge">{{ $step['step'] }}</span>
                    <div class="flex-1">
                        @if($step['intro'] ?? null)
                            <h2 class="text-xl font-extrabold text-slate-900">{{ $step['intro']['title'] ?? 'Profil' }}</h2>
                            @if(!empty($step['intro']['note']))
                                <p class="text-sm text-slate-700 mt-2">{{ $step['intro']['note'] }}</p>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="space-y-8 mt-6">
                    @foreach($step['questions'] as $q)
                        <fieldset class="space-y-3">
                            <legend class="text-base font-bold text-slate-900">{{ $q['text'] }}</legend>
                            @if(!empty($q['note']))
                                <p class="text-sm text-slate-700 whitespace-pre-line">{{ $q['note'] }}</p>
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
                                        <span class="text-sm font-medium text-slate-900">{{ $label }}</span>
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
                    @if($index < count($wizardSteps) - 1)
                        <button type="button" class="checkup-ok-btn" data-action="next">Lanjut</button>
                    @else
                        <button type="submit" class="checkup-ok-btn">Simpan Diagnostik</button>
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
    let current = 0;

    function showPanel(idx) {
        steps.forEach((el, i) => el.classList.toggle('hidden', i !== idx));
        const progress = Math.round(((idx + 1) / total) * 100);
        document.getElementById('progressBar').style.width = progress + '%';
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
