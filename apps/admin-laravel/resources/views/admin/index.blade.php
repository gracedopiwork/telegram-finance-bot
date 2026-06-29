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

<div class="card card-outline card-primary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-table mr-2"></i>Sync dashboard (Google Sheets)</h3>
    </div>
    <div class="card-body">
        <p class="text-muted mb-2">
            Menyalin tab <strong>Dashboard</strong> dari spreadsheet master (env bot: <code>DASHBOARD_MASTER_SPREADSHEET_ID</code>)
            ke semua baris <code>user_sheets</code> status aktif. Tab <strong>Transaksi</strong> tidak diubah.
            Setelah sync, proteksi sheet diterapkan ulang (hanya service account yang bisa edit; pelanggan <em>viewer</em> — tidak melihat rumus).
        </p>
        <p class="small text-muted mb-2">
            <strong>Auto-sync (Opsi 2):</strong> pasang Google Apps Script dari
            <code>shared/scripts/google-apps-script-dashboard-sync.js</code> di master sheet, set
            <code>DASHBOARD_SYNC_WEBHOOK_TOKEN</code> di <code>.env</code>, lalu edit tab Dashboard (versi di sel <code>Z1</code>).
            Webhook: <code>POST {{ url('/api/dashboard/sync-webhook') }}</code>
        </p>
        <p class="small text-muted mb-0">
            Template wajib punya tab <code>Transaksi</code> + <code>Dashboard</code>. Folder salinan disarankan Shared drive yang hanya berisi service account agar admin tidak melihat data transaksi di Drive.
        </p>
        <form method="post" action="{{ route('admin.dashboard-sync') }}" class="form-inline flex-wrap align-items-end" onsubmit="return confirm('Jalankan sync dashboard dengan versi ini?');">
            @csrf
            <div class="form-group mr-2 mb-2">
                <label for="sync-version" class="d-block small text-muted">Versi (contoh: v1.2)</label>
                <input type="text" name="version" id="sync-version" class="form-control" placeholder="v1.2" required maxlength="64" pattern="[a-zA-Z0-9._-]+" title="Huruf, angka, titik, strip, underscore">
            </div>
            <div class="form-check mr-3 mb-2">
                <input class="form-check-input" type="checkbox" name="dry_run" id="sync-dry-run" value="1">
                <label class="form-check-label" for="sync-dry-run">Dry run</label>
            </div>
            <button type="submit" class="btn btn-primary mb-2">
                <i class="fas fa-sync-alt mr-1"></i> Sync sekarang
            </button>
        </form>
    </div>
</div>

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
