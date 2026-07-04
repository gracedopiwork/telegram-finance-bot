@php
    $aiSource = $aiSource ?? null;
    $claudeConfigured = $claudeConfigured ?? app(\App\Services\ClaudeJsonService::class)->isConfigured();
    $tone = $tone ?? 'light';
    $class = $tone === 'dark'
        ? 'text-[11px] text-white/60 mb-2'
        : 'text-[11px] text-slate-500 mb-2';
@endphp
@if($aiSource === 'ai')
    <p class="{{ $class }}">Dipersonalisasi oleh dr. Financial (Claude AI) berdasarkan data Anda.</p>
@elseif($aiSource === 'rules')
    @if($claudeConfigured)
        <p class="{{ $class }}">Claude sudah dikonfigurasi di server, tapi API gagal merespons. Jalankan <code class="text-[10px]">php artisan portal:test-integrations</code> di server — biasanya API key tidak valid, billing Anthropic habis, atau model tidak tersedia.</p>
    @else
        <p class="{{ $class }}">Insight sementara dari aturan internal — isi <code class="text-[10px]">ANTHROPIC_API_KEY</code> di <code class="text-[10px]">apps/admin-laravel/.env</code> lalu <code class="text-[10px]">php artisan config:clear</code>.</p>
    @endif
@endif
