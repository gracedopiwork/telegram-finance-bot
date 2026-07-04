@php
    $aiSource = $aiSource ?? null;
    $tone = $tone ?? 'light';
    $class = $tone === 'dark'
        ? 'text-[11px] text-white/60 mb-2'
        : 'text-[11px] text-slate-500 mb-2';
@endphp
@if($aiSource === 'ai')
    <p class="{{ $class }}">Dipersonalisasi oleh dr. Financial (Claude AI) berdasarkan data Anda.</p>
@elseif($aiSource === 'rules')
    <p class="{{ $class }}">Insight sementara dari aturan internal — Claude belum terhubung atau respons AI gagal. Pastikan <code class="text-[10px]">ANTHROPIC_API_KEY</code> aktif di server lalu jalankan <code class="text-[10px]">php artisan cache:clear</code>.</p>
@endif
