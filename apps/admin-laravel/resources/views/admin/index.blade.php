@extends('admin.layouts.page')

@section('page_heading', 'Dashboard')
@section('page_subheading', 'Overview konten Company Profile YFD')

@section('main')

<div class="card card-outline card-success mb-4">
    <div class="card-body">
        <h4 class="mb-2"><i class="fas fa-stethoscope mr-2"></i>Selamat datang, Admin YFD!</h4>
        <p class="text-muted mb-3">Kelola konten website dari sidebar — Site Settings, Paket, Layanan, Tim Dokter, FAQ, dan Wealthpedia.</p>
        <a href="{{ route('admin.settings.index') }}" class="btn btn-success mr-2"><i class="fas fa-sliders-h mr-1"></i> Site Settings</a>
        <a href="{{ route('company.home') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-1"></i> Lihat Website</a>
    </div>
</div>

@php
    $ai = $aiHealth ?? [];
    $aiStatus = $ai['status'] ?? 'unknown';
    $aiCardClass = match ($aiStatus) {
        'ok' => 'card-success',
        'warning' => 'card-warning',
        'critical' => 'card-danger',
        default => 'card-secondary',
    };
    $aiBadgeClass = match ($aiStatus) {
        'ok' => 'badge-success',
        'warning' => 'badge-warning',
        'critical' => 'badge-danger',
        default => 'badge-secondary',
    };
    $totals = $ai['totals'] ?? ['success' => 0, 'rate_limit' => 0, 'fallback' => 0, 'error' => 0, 'total' => 0];
    $diag = $ai['diagnostics'] ?? ['laravel_token_set' => false, 'stats_table_ready' => false];
@endphp

<div class="card card-outline {{ $aiCardClass }} mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-robot mr-2"></i>Status AI Claude (7 hari terakhir)</h3>
        <span class="badge {{ $aiBadgeClass }} ml-2">{{ $ai['label'] ?? '—' }}</span>
    </div>
    <div class="card-body">
        <p class="mb-2">{{ $ai['message'] ?? '' }}</p>
        <div class="row text-center mb-3">
            <div class="col-3"><strong>{{ $totals['success'] }}</strong><br><small class="text-muted">Sukses AI</small></div>
            <div class="col-3"><strong class="text-danger">{{ $totals['rate_limit'] }}</strong><br><small class="text-muted">429 / limit</small></div>
            <div class="col-3"><strong class="text-warning">{{ $totals['fallback'] }}</strong><br><small class="text-muted">Parser lokal</small></div>
            <div class="col-3"><strong>{{ $totals['total'] }}</strong><br><small class="text-muted">Total</small></div>
        </div>
        @if(!empty($ai['fallback_rate']))
            <p class="small text-muted mb-1">Fallback + rate limit: <strong>{{ $ai['fallback_rate'] }}%</strong> dari total request.</p>
        @endif
        @if(!empty($ai['last_rate_limit_at']))
            <p class="small text-muted mb-1">Terakhir kena limit: {{ $ai['last_rate_limit_at'] }}</p>
        @endif
        @if(!empty($ai['last_detail']))
            <p class="small text-muted mb-2"><code>{{ $ai['last_detail'] }}</code></p>
        @endif
        @if(!empty($ai['should_upgrade']))
            <div class="alert alert-warning mb-2 py-2">
                <strong>Saran:</strong> cek kuota dan billing di
                <a href="https://console.anthropic.com/settings/billing" target="_blank" rel="noopener">Anthropic Console</a>.
            </div>
        @endif
        @if(($totals['total'] ?? 0) === 0)
            <ul class="small mb-2 pl-3">
                <li>Tabel statistik: <strong>{{ ($diag['stats_table_ready'] ?? false) ? 'siap' : 'belum migrate' }}</strong></li>
                <li>Token Laravel: <strong>{{ ($diag['laravel_token_set'] ?? false) ? 'sudah di-set' : 'belum di-set' }}</strong></li>
                <li>Bot harus punya <code>LARAVEL_APP_URL</code> + token yang sama, lalu di-restart.</li>
            </ul>
        @endif
        <p class="small text-muted mb-0">
            Data dikirim otomatis dari bot (<code>POST /api/bot/ai-health</code>). Butuh
            <code>BOT_INTERNAL_API_TOKEN</code> + <code>LARAVEL_APP_URL</code> di <code>apps/bot-python/.env</code>.
        </p>
    </div>
</div>

@php
    $sh = $serverHealth ?? [];
    $sc = $serverCosts ?? [];
    $shStatus = $sh['status'] ?? 'unknown';
    $shCardClass = match ($shStatus) {
        'ok' => 'card-success',
        'warning' => 'card-warning',
        'critical' => 'card-danger',
        default => 'card-secondary',
    };
    $shUsage = $sh['usage'] ?? [];
    $shTier = $sh['tier'] ?? [];
@endphp

<div class="card card-outline {{ $shCardClass }} mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-server mr-2"></i>Server &amp; Biaya</h3>
        <a href="{{ route('admin.server-health.index') }}" class="btn btn-sm btn-outline-secondary">Detail</a>
    </div>
    <div class="card-body">
        <div class="row text-center mb-2">
            <div class="col-4">
                <strong>{{ number_format($shUsage['active_users_30d'] ?? 0) }}</strong>
                <div class="small text-muted">User aktif 30h</div>
            </div>
            <div class="col-4">
                <strong>{{ $shTier['label'] ?? '—' }}</strong>
                <div class="small text-muted">Tier VPS</div>
            </div>
            <div class="col-4">
                <strong>Rp {{ number_format($sc['total_monthly_idr'] ?? 0, 0, ',', '.') }}</strong>
                <div class="small text-muted">Estimasi/bulan</div>
            </div>
        </div>
        @if(!empty($sh['alerts'][0]['message']))
            <p class="small mb-0">{{ $sh['alerts'][0]['message'] }}</p>
        @endif
    </div>
</div>

{{-- Kartu sinkronisasi lama sudah dihapus: platform sekarang full web dashboard. --}}

<div class="row">
    @php
        $cards = [
            ['route' => 'admin.settings.index', 'label' => 'Settings',  'count' => $stats['settings'], 'icon' => 'fa-cog',             'bg' => 'bg-info'],
            ['route' => 'admin.packages.index', 'label' => 'Paket',     'count' => $stats['packages'], 'icon' => 'fa-box-open',       'bg' => 'bg-primary'],
            ['route' => 'admin.services.index', 'label' => 'Layanan',   'count' => $stats['services'], 'icon' => 'fa-hand-holding-medical', 'bg' => 'bg-success'],
            ['route' => 'admin.advisors.index', 'label' => 'Tim Dokter','count' => $stats['advisors'], 'icon' => 'fa-user-md',       'bg' => 'bg-danger'],
            ['route' => 'admin.faqs.index',     'label' => 'FAQ',       'count' => $stats['faqs'],     'icon' => 'fa-question-circle','bg' => 'bg-warning'],
            ['route' => 'admin.articles.index', 'label' => 'Artikel',   'count' => $stats['articles'], 'icon' => 'fa-book-open',      'bg' => 'bg-secondary'],
        ];
    @endphp
    @foreach($cards as $c)
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <a href="{{ route($c['route']) }}" class="text-reset">
                <div class="small-box {{ $c['bg'] }} mb-0">
                    <div class="inner">
                        <h3>{{ $c['count'] }}</h3>
                        <p>{{ $c['label'] }}</p>
                    </div>
                    <div class="icon"><i class="fas {{ $c['icon'] }}"></i></div>
                    <span class="small-box-footer">Kelola <i class="fas fa-arrow-circle-right"></i></span>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-8 mb-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Quick Actions</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('admin.packages.create') }}" class="btn btn-outline-primary btn-block"><i class="fas fa-plus mr-1"></i> Tambah Paket</a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('admin.advisors.create') }}" class="btn btn-outline-primary btn-block"><i class="fas fa-user-plus mr-1"></i> Tambah Tim Dokter</a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('admin.faqs.create') }}" class="btn btn-outline-primary btn-block"><i class="fas fa-plus mr-1"></i> Tambah FAQ</a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="{{ route('admin.articles.create') }}" class="btn btn-outline-primary btn-block"><i class="fas fa-edit mr-1"></i> Tulis Artikel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Artikel Terbaru</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentArticles as $a)
                        <li class="list-group-item">
                            <a href="{{ route('admin.articles.edit', $a) }}" class="d-block font-weight-bold">{{ Str::limit($a->title, 42) }}</a>
                            <small class="text-muted">{{ $a->category }} · {{ $a->updated_at->diffForHumans() }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Belum ada artikel.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
