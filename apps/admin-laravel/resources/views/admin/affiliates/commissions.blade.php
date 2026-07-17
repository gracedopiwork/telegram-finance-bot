@extends('adminlte::page')

@section('title', 'Komisi Affiliate')

@section('content_header')
    <h1>Komisi Affiliate</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-sm btn-outline-secondary">Affiliate</a>
    <a href="{{ route('admin.affiliates.claims') }}" class="btn btn-sm btn-outline-secondary">Klaim</a>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Affiliate</th>
                    <th>Order</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($commissions as $c)
                    <tr>
                        <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $c->affiliate?->email }} <span class="text-muted">({{ $c->affiliate?->referral_code }})</span></td>
                        <td>{{ $c->order?->order_code }}</td>
                        <td>Rp {{ number_format($c->amount, 0, ',', '.') }}</td>
                        <td>{{ $c->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada komisi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($commissions->hasPages())
        <div class="card-footer">{{ $commissions->links() }}</div>
    @endif
</div>
@stop
