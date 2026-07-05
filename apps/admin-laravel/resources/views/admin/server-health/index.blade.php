@extends('admin.layouts.page')

@section('page_heading', 'Kesehatan Server & Biaya')
@section('page_subheading', 'Pantau resource VPS, kapasitas user, dan proyeksi biaya server + AI')

@section('page_actions')
<button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
    <i class="fas fa-sync mr-1"></i> Refresh
</button>
@endsection

@section('main')
@php
    $fmtBytes = function (?int $bytes): string {
        if ($bytes === null || $bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return round($bytes / (1024 ** $pow), 1).' '.$units[$pow];
    };
    $fmtIdr = fn (int $n) => 'Rp '.number_format($n, 0, ',', '.');
    $status = $snapshot['status'] ?? 'unknown';
    $cardClass = match ($status) {
        'ok' => 'card-success',
        'warning' => 'card-warning',
        'critical' => 'card-danger',
        default => 'card-secondary',
    };
    $badgeClass = match ($status) {
        'ok' => 'badge-success',
        'warning' => 'badge-warning',
        'critical' => 'badge-danger',
        default => 'badge-secondary',
    };
    $resources = $snapshot['resources'] ?? [];
    $usage = $snapshot['usage'] ?? [];
    $tier = $snapshot['tier'] ?? [];
    $alerts = $snapshot['alerts'] ?? [];
    $memory = $resources['memory'] ?? [];
@endphp

<div class="card card-outline {{ $cardClass }} mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-heartbeat mr-2"></i>Status Server</h3>
        <span class="badge {{ $badgeClass }} ml-2">{{ strtoupper($status) }}</span>
        <span class="text-muted small ml-2">Cek: {{ $snapshot['checked_at'] ?? '—' }}</span>
    </div>
    <div class="card-body">
        @foreach($alerts as $alert)
            @php $lvl = $alert['level'] ?? 'ok'; @endphp
            <div class="alert alert-{{ $lvl === 'critical' ? 'danger' : ($lvl === 'warning' ? 'warning' : 'success') }} py-2 mb-2">
                {{ $alert['message'] }}
            </div>
        @endforeach
        <p class="small text-muted mb-0">
            Host: <code>{{ $snapshot['hostname'] ?? '—' }}</code> ·
            PHP {{ $snapshot['php_version'] ?? '—' }} ·
            Laravel {{ $snapshot['laravel_version'] ?? '—' }}
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="card card-outline card-info h-100">
            <div class="card-header"><h3 class="card-title mb-0">Resource Server</h3></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>CPU cores</td>
                        <td class="text-right"><strong>{{ $resources['cpu_count'] ?? '—' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Load (1m)</td>
                        <td class="text-right"><strong>{{ $resources['load_1m'] ?? '—' }}</strong>
                            @if(isset($resources['load_ratio']))
                                <span class="text-muted small">(ratio {{ $resources['load_ratio'] }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>RAM terpakai</td>
                        <td class="text-right">
                            <strong>{{ $memory['used_percent'] !== null ? $memory['used_percent'].'%' : '—' }}</strong>
                            @if($memory['total_bytes'] ?? null)
                                <div class="text-muted small">{{ $fmtBytes($memory['available_bytes']) }} free / {{ $fmtBytes($memory['total_bytes']) }}</div>
                            @else
                                <div class="text-muted small">Hanya tersedia di Linux VPS</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Disk (storage)</td>
                        <td class="text-right">
                            <strong>{{ $resources['disk_used_percent'] !== null ? $resources['disk_used_percent'].'%' : '—' }}</strong>
                            @if($resources['disk_total_bytes'] ?? null)
                                <div class="text-muted small">{{ $fmtBytes($resources['disk_free_bytes']) }} free</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>MySQL koneksi</td>
                        <td class="text-right"><strong>{{ $usage['db_connections'] ?? '—' }}</strong></td>
                    </tr>
                    <tr>
                        <td>PHP memory peak</td>
                        <td class="text-right"><strong>{{ $fmtBytes($resources['php_memory_peak_bytes'] ?? null) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="card card-outline card-primary h-100">
            <div class="card-header"><h3 class="card-title mb-0">Penggunaan Aplikasi (30 hari)</h3></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>User aktif (catat transaksi)</td>
                        <td class="text-right"><strong>{{ number_format($usage['active_users_30d'] ?? 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Lisensi teraktivasi</td>
                        <td class="text-right"><strong>{{ number_format($usage['licenses_activated'] ?? 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Order paid</td>
                        <td class="text-right"><strong>{{ number_format($usage['paid_orders'] ?? 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Transaksi</td>
                        <td class="text-right"><strong>{{ number_format($usage['transactions_30d'] ?? 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Baseline data</td>
                        <td class="text-right"><strong>{{ number_format($usage['baselines'] ?? 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Request AI (bot)</td>
                        <td class="text-right"><strong>{{ number_format($usage['ai_parses_30d'] ?? 0) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="card card-outline card-success h-100">
            <div class="card-header"><h3 class="card-title mb-0">Tier VPS Rekomendasi</h3></div>
            <div class="card-body">
                <h4 class="mb-1">{{ $tier['label'] ?? '—' }}</h4>
                <p class="text-muted small mb-2">{{ $tier['notes'] ?? '' }}</p>
                <ul class="list-unstyled mb-3">
                    <li><i class="fas fa-microchip text-muted mr-1"></i> {{ $tier['vcpu'] ?? '—' }} vCPU · {{ $tier['ram_gb'] ?? '—' }} GB RAM</li>
                    <li><i class="fas fa-users text-muted mr-1"></i> Hingga {{ number_format($tier['max_active_users'] ?? 0) }} user aktif/bulan</li>
                    <li><i class="fas fa-money-bill text-muted mr-1"></i> Estimasi sewa: <strong>{{ $fmtIdr((int) ($tier['monthly_idr'] ?? 0)) }}/bulan</strong></li>
                </ul>
                @if($costs['upgrade_next_tier'] ?? null)
                    <div class="alert alert-light border py-2 mb-0 small">
                        <strong>Upgrade berikutnya:</strong> {{ $costs['upgrade_next_tier']['label'] }}
                        ({{ $fmtIdr((int) $costs['upgrade_next_tier']['monthly_idr']) }}/bln,
                        max {{ number_format($costs['upgrade_next_tier']['max_active_users']) }} user)
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-warning mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calculator mr-2"></i>Proyeksi Biaya Bulanan (Server + AI)</h3>
    </div>
    <div class="card-body">
        <div class="row text-center mb-3">
            <div class="col-md-4">
                <div class="h4 mb-0">{{ $fmtIdr((int) ($costs['server_monthly_idr'] ?? 0)) }}</div>
                <small class="text-muted">Sewa VPS (tier {{ $tier['label'] ?? '—' }})</small>
            </div>
            <div class="col-md-4">
                <div class="h4 mb-0">{{ $fmtIdr((int) ($costs['ai_monthly_idr'] ?? 0)) }}</div>
                <small class="text-muted">API AI ({{ $costs['ai_parses_30d'] ?? 0 }} parse × {{ $fmtIdr((int) ($costs['cost_per_parse_idr'] ?? 0)) }})</small>
            </div>
            <div class="col-md-4">
                <div class="h4 mb-0 text-success">{{ $fmtIdr((int) ($costs['total_monthly_idr'] ?? 0)) }}</div>
                <small class="text-muted">Total estimasi / bulan</small>
            </div>
        </div>
        <p class="small text-muted">
            Provider AI asumsi: <code>{{ $costs['ai_provider'] ?? 'claude_haiku' }}</code>.
            Edit tier &amp; harga di <code>config/server_capacity.php</code> atau env
            <code>SERVER_AI_COST_PARSE_*</code>.
        </p>
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Tier</th>
                    <th class="text-right">Max user aktif</th>
                    <th class="text-right">VPS/bulan</th>
                    <th class="text-right">AI (estimasi)*</th>
                    <th class="text-right">Total*</th>
                </tr>
            </thead>
            <tbody>
                @foreach($costs['tiers'] ?? [] as $t)
                    <tr class="{{ ($t['key'] ?? '') === ($tier['key'] ?? '') ? 'table-success' : '' }}">
                        <td><strong>{{ $t['label'] }}</strong><br><span class="text-muted small">{{ $t['notes'] ?? '' }}</span></td>
                        <td class="text-right">{{ number_format($t['max_active_users'] ?? 0) }}</td>
                        <td class="text-right">{{ $fmtIdr((int) ($t['monthly_idr'] ?? 0)) }}</td>
                        <td class="text-right">{{ $fmtIdr((int) ($t['ai_estimate_idr'] ?? 0)) }}</td>
                        <td class="text-right"><strong>{{ $fmtIdr((int) ($t['total_estimate_idr'] ?? 0)) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="small text-muted mb-0">* Kolom AI memproyeksikan ~30 parse/user aktif × biaya per parse. Angka aktual tergantung pemakaian.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title mb-0">Integrasi</h3></div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Midtrans: <strong>{{ ($integrations['midtrans'] ?? false) ? 'Siap' : 'Belum' }}</strong></li>
                    <li>Claude API: <strong>{{ ($integrations['claude'] ?? false) ? 'Siap' : 'Belum' }}</strong></li>
                    <li>AI health (7 hari): <strong>{{ $aiHealth['label'] ?? '—' }}</strong></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title mb-0">Kapan upgrade server?</h3></div>
            <div class="card-body small">
                <ul class="mb-0 pl-3">
                    <li>RAM &gt; 75% konsisten → rencanakan upgrade VPS</li>
                    <li>Load CPU ratio &gt; 1.2 → web mulai lambat saat ramai login</li>
                    <li>User aktif mendekati 80% kapasitas tier → naik tier</li>
                    <li>Response portal &gt; 3 detik atau error 502 → segera upgrade</li>
                </ul>
                <p class="text-muted mt-2 mb-0">CLI: <code>php artisan portal:server-health</code></p>
            </div>
        </div>
    </div>
</div>
@endsection
