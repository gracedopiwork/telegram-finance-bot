@php
    $baseline = $baseline ?? null;
    if ($baseline === null) {
        return;
    }
    $fmt = fn (int $n) => \App\Support\RupiahFormat::format($n);
    $onboarding = app(\App\Services\PortalOnboardingService::class);
    $hasDiagnostic = $onboarding->hasFinancialDiagnostic($baseline);
    $hasSnapshot = trim((string) ($baseline->current_goal ?? '')) !== ''
        || collect(['avg_monthly_income', 'emergency_fund', 'cash_savings', 'total_debt'])
            ->contains(fn ($f) => $baseline->{$f} !== null && (int) $baseline->{$f} > 0);
    $stageMeta = $stageMeta ?? [];
@endphp

@if($hasSnapshot)
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6">
    <div class="bg-slate-50 px-5 py-4 border-b flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-bold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined">inventory_2</span>
            Baseline Data — Snapshot Keuangan
        </h3>
        <a href="{{ route('portal.baseline.create', ['section' => 'snapshot']) }}"
           class="text-xs font-semibold text-navy-800 hover:underline">Perbarui</a>
    </div>
    <div class="p-5 space-y-3 text-sm">
        @if(trim((string) ($baseline->current_goal ?? '')) !== '')
            <div class="rounded-xl bg-gold-50 border border-gold-200 p-3">
                <div class="text-xs font-bold text-amber-800 uppercase">Target / Goal Saat Ini</div>
                <div class="font-medium text-navy-800 mt-1">{{ \App\Support\RupiahFormat::formatText($baseline->current_goal) }}</div>
            </div>
        @endif
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach([
                'avg_monthly_income' => 'Pendapatan/bulan',
                'cash_savings' => 'Tabungan',
                'total_debt' => 'Total utang',
                'emergency_fund' => 'Dana darurat',
            ] as $field => $label)
                @if($baseline->{$field})
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">{{ $label }}</div>
                        <div class="font-bold text-navy-800">{{ $fmt((int) $baseline->{$field}) }}</div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endif

@if($hasDiagnostic && ($baseline->stage_label || $baseline->financial_stage))
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 mb-6">
    <h3 class="font-bold text-navy-800 text-lg mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined">stairs</span>
        Hasil Diagnostik Tahap Keuangan
    </h3>
    <div class="flex flex-wrap items-start gap-4">
        @if(!empty($stageMeta['emoji']))
            <div class="text-4xl">{{ $stageMeta['emoji'] }}</div>
        @endif
        <div class="min-w-0 flex-1">
            <div class="text-2xl font-extrabold text-navy-800">{{ $baseline->stage_label ?? '—' }}</div>
            <div class="text-sm text-slate-500 mt-1">
                {{ $stageMeta['phase'] ?? '' }}
                @if($baseline->financial_stage_score)
                    · Skor {{ $baseline->financial_stage_score }}/39
                @endif
            </div>
            @if(!empty($stageMeta['diagnosis']))
                <p class="mt-3 text-sm text-slate-600 leading-relaxed">{{ $stageMeta['diagnosis'] }}</p>
            @endif
        </div>
    </div>
</div>
@elseif(!$hasSnapshot)
    @include('portal.partials.empty-state', [
        'title' => 'Baseline data belum lengkap',
        'message' => 'Lengkapi snapshot keuangan (target, pendapatan, utang, tabungan) agar insight AI lebih personal.',
    ])
    <div class="mt-4 mb-6">
        <a href="{{ route('portal.baseline.create', ['section' => 'snapshot']) }}"
           class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-5 py-3 rounded-xl text-sm">
            <span class="material-symbols-outlined text-lg">inventory_2</span>
            Isi Snapshot Keuangan
        </a>
    </div>
@endif
