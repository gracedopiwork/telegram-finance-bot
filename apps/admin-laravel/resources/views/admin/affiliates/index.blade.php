@extends('adminlte::page')

@section('title', 'Affiliate')

@section('content_header')
    <h1>Affiliate / Referral</h1>
@stop

@section('content')
<div class="mb-3">
    <form method="GET" class="form-inline">
        <input type="text" name="q" value="{{ $q }}" class="form-control mr-2" placeholder="Cari email / kode / nama">
        <button class="btn btn-primary">Cari</button>
        <a href="{{ route('admin.affiliates.create') }}" class="btn btn-success ml-2">
            <i class="fas fa-plus mr-1"></i> Tambah Affiliate
        </a>
        <a href="{{ route('admin.affiliates.claims') }}" class="btn btn-outline-secondary ml-2">Klaim</a>
        <a href="{{ route('admin.affiliates.commissions') }}" class="btn btn-outline-secondary ml-2">Komisi</a>
        <a href="{{ route('admin.settings.index', ['group' => 'affiliate']) }}" class="btn btn-outline-secondary ml-2">Pengaturan</a>
    </form>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama / Email</th>
                    <th>Komisi</th>
                    <th>Saldo</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($affiliates as $a)
                    <tr>
                        <td><code>{{ $a->referral_code }}</code></td>
                        <td>
                            <div class="font-weight-bold">{{ $a->name ?: '—' }}</div>
                            <div class="text-muted small">{{ $a->email }}</div>
                        </td>
                        <td>{{ $a->commissions_count }}</td>
                        <td>Rp {{ number_format($a->available_balance ?? 0, 0, ',', '.') }}</td>
                        <td>
                            @if($a->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.affiliates.show', $a) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            <form action="{{ route('admin.affiliates.toggle', $a) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">{{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada affiliate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($affiliates->hasPages())
        <div class="card-footer">{{ $affiliates->links() }}</div>
    @endif
</div>
@stop
