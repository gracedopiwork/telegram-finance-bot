@php
    $ftsaProfile = $ftsaProfile ?? null;
    $baseline = $baseline ?? null;
    if ($ftsaProfile === null) {
        return;
    }
    $filled = null;
    $total = 32;
    if ($baseline !== null) {
        $summary = app(\App\Services\FtsaAnswerSummaryService::class)->scoreSummary($baseline);
        $filled = $summary['filled'] ?? null;
        $total = $summary['total'] ?? 32;
    }
    $assessedAt = $baseline?->assessed_at?->format('d M Y');
    $detailUrl = $detailUrl ?? null;
@endphp

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 {{ $class ?? '' }}">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $title ?? 'Hasil FTSA Premium' }}</div>
            <h3 class="text-xl font-extrabold text-navy-800 mt-1">{{ $ftsaProfile['archetype'] ?? '—' }}</h3>
            @if($filled !== null)
                <p class="text-sm text-slate-600 mt-1">{{ $filled }}/{{ $total }} pertanyaan terisi</p>
            @endif
            @if($assessedAt)
                <p class="text-xs text-slate-500 mt-1">Terakhir diisi: {{ $assessedAt }}</p>
            @endif
        </div>
        @if($detailUrl)
            <a href="{{ $detailUrl }}" class="text-xs font-semibold text-navy-800 hover:underline shrink-0">Detail →</a>
        @endif
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach($ftsaProfile['domains'] ?? [] as $domain)
            <div class="rounded-xl bg-slate-50 px-3 py-2.5 text-center">
                <div class="text-xs font-bold text-slate-500">{{ $domain['label'] ?? strtoupper($domain['key'] ?? '') }}</div>
                <div class="text-lg font-extrabold text-navy-800">{{ (int) ($domain['score'] ?? 0) }}<span class="text-xs text-slate-400 font-semibold">/40</span></div>
            </div>
        @endforeach
    </div>
</div>
