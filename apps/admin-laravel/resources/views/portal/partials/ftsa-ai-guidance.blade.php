@php
    $ftsaInsights = $ftsaAiGuidance['insights'] ?? [];
    $ftsaRecommendations = $ftsaAiGuidance['recommendations'] ?? [];
    $ftsaAiSource = $ftsaAiGuidance['source'] ?? 'none';
@endphp

@if(!empty($ftsaInsights))
    <div class="bg-gradient-to-r from-navy-800 to-navy-600 rounded-2xl p-5 sm:p-6 text-white mb-6">
        <h3 class="font-bold mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-gold-400">lightbulb</span> Insight FTSA
        </h3>
        @if($ftsaAiSource === 'ai')
            <p class="text-[11px] text-white/60 mb-3">Dipersonalisasi oleh dr. Financial (AI) berdasarkan hasil kuesioner Anda.</p>
        @elseif($ftsaAiSource === 'rules')
            <p class="text-[11px] text-white/50 mb-3">Insight berbasis aturan (AI belum aktif — set GEMINI_API_KEY).</p>
        @endif
        <ul class="space-y-2 text-sm text-white/90">
            @foreach($ftsaInsights as $insight)
                <li class="flex gap-2"><span class="text-gold-400 shrink-0">→</span>{{ $insight }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($ftsaRecommendations))
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 mb-6">
        <h3 class="font-bold text-navy-800 mb-1">Rekomendasi untuk Profil Anda</h3>
        @include('portal.partials.ai-source-badge', ['aiSource' => $ftsaAiSource])
        <ul class="space-y-2 text-sm text-slate-700">
            @foreach($ftsaRecommendations as $rec)
                <li class="flex gap-2"><span class="text-navy-600 font-bold shrink-0">•</span>{{ $rec }}</li>
            @endforeach
        </ul>
    </div>
@endif
