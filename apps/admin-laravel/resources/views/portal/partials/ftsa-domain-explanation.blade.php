@php
    $domainScores = $domainScores ?? [];
@endphp

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
    <h3 class="font-bold text-navy-800 text-lg mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined">menu_book</span> Penjelasan FTSA
    </h3>
    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
        FTSA (Financial Therapy &amp; Strategic Action) mengukur pola behavioral finansial lewat 32 pertanyaan
        dalam empat domain. Domain dengan skor tertinggi menentukan archetype dominan.
    </p>
    <div class="space-y-3">
        @foreach($domainScores as $key => $d)
            <div class="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                <div class="font-semibold text-navy-800 text-sm">
                    {{ $d['meta']['code'] ?? strtoupper($key) }}
                    @if(!empty($d['meta']['archetype_label']))
                        <span class="text-slate-500 font-normal">· {{ $d['meta']['archetype_label'] }}</span>
                    @endif
                </div>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $d['meta']['summary'] ?? $d['meta']['label'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
    <p class="text-xs text-slate-500 mt-4 pt-3 border-t border-slate-100">
        Evaluasi baseline setiap <strong>6 bulan</strong> · Evaluasi FTSA setiap <strong>12 bulan</strong> setelah unlock premium.
    </p>
</div>
