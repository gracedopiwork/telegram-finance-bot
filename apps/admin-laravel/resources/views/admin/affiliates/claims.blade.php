@extends('adminlte::page')

@section('title', 'Klaim Affiliate')

@section('content_header')
    <h1>Klaim Affiliate</h1>
@stop

@section('content')
<div class="mb-3">
    @foreach(['pending','approved','paid','rejected','all'] as $s)
        <a href="{{ route('admin.affiliates.claims', ['status' => $s]) }}"
           class="btn btn-sm {{ $status === $s ? 'btn-primary' : 'btn-outline-secondary' }}">{{ ucfirst($s) }}</a>
    @endforeach
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-sm btn-outline-secondary ml-2">Affiliate</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Affiliate</th>
                    <th>Gross</th>
                    <th>Pajak</th>
                    <th>Net</th>
                    <th>NPWP</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $claim)
                    @php [$label, $badge] = $claim->statusBadge(); @endphp
                    <tr>
                        <td>#{{ $claim->id }}</td>
                        <td>
                            <div>{{ $claim->affiliate?->email }}</div>
                            <div class="small text-muted">{{ $claim->affiliate?->referral_code }}</div>
                        </td>
                        <td>Rp {{ number_format($claim->gross_amount, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($claim->tax_amount, 0, ',', '.') }} ({{ $claim->tax_percent }}%)</td>
                        <td><strong>Rp {{ number_format($claim->net_amount, 0, ',', '.') }}</strong></td>
                        <td>{{ $claim->npwp_snapshot ?: '—' }}</td>
                        <td><span class="badge badge-{{ $badge }}">{{ $label }}</span></td>
                        <td style="min-width:220px">
                            @if($claim->status === 'pending')
                                <form method="POST" action="{{ route('admin.affiliates.claims.process', $claim) }}" class="mb-1">
                                    @csrf
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn btn-xs btn-info">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('admin.affiliates.claims.process', $claim) }}" class="mb-1"
                                      onsubmit="return confirm('Tolak klaim? Saldo akan dikembalikan.')">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected">
                                    <input type="text" name="admin_note" class="form-control form-control-sm mb-1" placeholder="Alasan (opsional)">
                                    <button class="btn btn-xs btn-danger">Tolak</button>
                                </form>
                            @elseif($claim->status === 'approved')
                                <form method="POST" action="{{ route('admin.affiliates.claims.process', $claim) }}"
                                      onsubmit="return confirm('Tandai sudah ditransfer manual?')">
                                    @csrf
                                    <input type="hidden" name="status" value="paid">
                                    <button class="btn btn-xs btn-success">Tandai dibayar</button>
                                </form>
                            @else
                                <span class="text-muted small">{{ $claim->processed_at?->format('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada klaim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($claims->hasPages())
        <div class="card-footer">{{ $claims->links() }}</div>
    @endif
</div>
@stop
