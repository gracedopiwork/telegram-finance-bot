@php
    $aiSource = $aiSource ?? null;
    $tone = $tone ?? 'light';
    $class = $tone === 'dark'
        ? 'text-[11px] text-white/60 mb-2'
        : 'text-[11px] text-slate-500 mb-2';
@endphp
@if($aiSource === 'ai')
    <p class="{{ $class }}">Dipersonalisasi oleh dr. Financial (AI) berdasarkan data Anda.</p>
@elseif($aiSource === 'rules')
    <p class="{{ $class }}">Insight berbasis aturan (set <code class="text-[10px]">GEMINI_API_KEY</code> untuk AI penuh).</p>
@endif
