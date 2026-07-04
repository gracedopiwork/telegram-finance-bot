@php
    $ftsaInsights = $ftsaAiGuidance['insights'] ?? [];
    $ftsaRecommendations = $ftsaAiGuidance['recommendations'] ?? [];
    $ftsaAiSource = $ftsaAiGuidance['source'] ?? 'none';
    $ftsaClaudeConfigured = $ftsaAiGuidance['claude_configured'] ?? app(\App\Services\ClaudeJsonService::class)->isConfigured();
@endphp

@if(!empty($ftsaInsights))
    <div class="bg-gradient-to-r from-navy-800 to-navy-600 rounded-2xl p-5 sm:p-6 text-white mb-6">
        <h3 class="font-bold mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-gold-400">lightbulb</span> Insight FTSA
        </h3>
        @if($ftsaAiSource === 'ai')
            <p class="text-[11px] text-white/60 mb-3">Dipersonalisasi oleh dr. Financial (Claude AI) berdasarkan FTSA dan baseline Anda.</p>
        @elseif($ftsaAiSource === 'rules')
            @if($ftsaClaudeConfigured)
                <p class="text-[11px] text-white/50 mb-3">Insight sementara — Claude dikonfigurasi tapi API gagal. Cek <code class="text-[10px]">portal:test-integrations</code> di server.</p>
            @else
                <p class="text-[11px] text-white/50 mb-3">Insight sementara dari aturan internal — isi ANTHROPIC_API_KEY di server.</p>
            @endif
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
        @include('portal.partials.ai-source-badge', ['aiSource' => $ftsaAiSource, 'claudeConfigured' => $ftsaClaudeConfigured])
        <ul class="space-y-2 text-sm text-slate-700">
            @foreach($ftsaRecommendations as $rec)
                <li class="flex gap-2"><span class="text-navy-600 font-bold shrink-0">•</span>{{ $rec }}</li>
            @endforeach
        </ul>
    </div>
@endif
