@extends('adminlte::page')

@section('title', 'Affiliate Detail')

@section('content_header')
    <h1>Affiliate {{ $affiliate->referral_code }}</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

@php
    $copySummary = implode("\n", [
        'Nama: '.($affiliate->name ?: '—'),
        'Email: '.($affiliate->email ?: '—'),
        'Lisensi: '.($affiliate->license?->license_key ?: '—'),
        'Kode Affiliate: '.($affiliate->referral_code ?: '—'),
    ]);
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Keterangan siap copy</strong></div>
            <div class="card-body">
                <textarea id="affiliateCopySummary" class="form-control small" rows="5" readonly
                          style="resize:none; background:#f8f9fa;">{{ $copySummary }}</textarea>
                <button type="button" class="btn btn-sm btn-primary btn-block mt-2" id="btnCopyAffiliateSummary">
                    <i class="fas fa-copy mr-1"></i>Copy semua
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <p><strong>Email:</strong> {{ $affiliate->email }}</p>
                <p><strong>Nama:</strong> {{ $affiliate->name ?: '—' }}</p>
                <p><strong>NPWP:</strong> {{ $affiliate->npwp ?: '—' }}</p>
                <p><strong>Rekening:</strong>
                    @if($affiliate->bank_name || $affiliate->bank_account_number)
                        <br>{{ $affiliate->bank_name ?: '—' }}
                        <br><code>{{ $affiliate->bank_account_number ?: '—' }}</code>
                        <br>{{ $affiliate->bank_account_name ?: '—' }}
                    @else
                        —
                    @endif
                </p>
                <p><strong>Lisensi:</strong> {{ $affiliate->license?->license_key ?: '—' }}</p>
                <p><strong>License ID:</strong> {{ $affiliate->license_id ?: '—' }}</p>
                <p><strong>Saldo tersedia:</strong> Rp {{ number_format($balance, 0, ',', '.') }}</p>
                <p><strong>Referral masuk:</strong> {{ $referredCount }} orang</p>
                <p><strong>Status:</strong> {{ $affiliate->is_active ? 'Aktif' : 'Nonaktif' }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Ubah kode affiliate</strong></div>
            <form method="POST" action="{{ route('admin.affiliates.update', $affiliate) }}">
                @csrf
                @method('PATCH')
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="name" value="{{ old('name', $affiliate->name) }}" class="form-control" maxlength="120">
                    </div>
                    <div class="form-group mb-0">
                        <label>Kode affiliate</label>
                        <input type="text" name="referral_code" value="{{ old('referral_code', $affiliate->referral_code) }}"
                               class="form-control text-uppercase" maxlength="32" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Orang yang masuk lewat kode ini</strong>
                <span class="badge badge-info">{{ $referredCount }}</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama / Email</th>
                            <th>Order</th>
                            <th>Produk</th>
                            <th>Status</th>
                            <th>Lisensi</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($affiliate->referredOrders as $refOrder)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $refOrder->full_name ?: '—' }}</div>
                                    <div class="text-muted small">{{ $refOrder->email }}</div>
                                    @if($refOrder->phone)
                                        <div class="text-muted small">{{ $refOrder->phone }}</div>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $refOrder) }}">
                                        {{ $refOrder->order_code }}
                                    </a>
                                </td>
                                <td class="small">{{ $refOrder->digitalProduct?->name ?? $refOrder->product_name ?? $refOrder->plan ?? '—' }}</td>
                                <td>
                                    @php [$lbl, $color] = $refOrder->statusBadge(); @endphp
                                    <span class="badge badge-{{ $color }}">{{ $lbl }}</span>
                                    @if($refOrder->isAdminComplimentary())
                                        <span class="badge badge-info">Gratis</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($refOrder->license)
                                        <code>{{ $refOrder->license->license_key }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ ($refOrder->paid_at ?? $refOrder->created_at)?->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada orang yang masuk pakai kode {{ $affiliate->referral_code }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Komisi terbaru</strong></div>
            <div class="card-body table-responsive p-0">
                <table class="table mb-0">
                    <thead><tr><th>Order</th><th>Jumlah</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        @forelse($affiliate->commissions as $c)
                            <tr>
                                <td>
                                    @if($c->order)
                                        <a href="{{ route('admin.orders.show', $c->order) }}">{{ $c->order->order_code }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>Rp {{ number_format($c->amount, 0, ',', '.') }}</td>
                                <td>{{ $c->status }}</td>
                                <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada komisi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><strong>Klaim</strong></div>
            <div class="card-body table-responsive p-0">
                <table class="table mb-0">
                    <thead><tr><th>ID</th><th>Net</th><th>Rekening</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        @forelse($affiliate->claims as $claim)
                            <tr>
                                <td>#{{ $claim->id }}</td>
                                <td>Rp {{ number_format($claim->net_amount, 0, ',', '.') }}</td>
                                <td class="small">
                                    @if($claim->bank_name)
                                        {{ $claim->bank_name }} · {{ $claim->bank_account_number }} · {{ $claim->bank_account_name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $claim->status }}</td>
                                <td>{{ $claim->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada klaim.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
(function () {
    var btn = document.getElementById('btnCopyAffiliateSummary');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var el = document.getElementById('affiliateCopySummary');
        if (!el) return;
        var text = el.value;
        var done = function () {
            var prev = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i>Tersalin!';
            setTimeout(function () { btn.innerHTML = prev; }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            el.focus();
            el.select();
            document.execCommand('copy');
            done();
        }
    });
})();
</script>
@stop
