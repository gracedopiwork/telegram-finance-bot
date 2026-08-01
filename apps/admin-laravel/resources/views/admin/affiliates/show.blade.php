@extends('adminlte::page')

@section('title', 'Affiliate Detail')

@section('content_header')
    <h1>Affiliate {{ $affiliate->referral_code }}</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p><strong>Email:</strong> {{ $affiliate->email }}</p>
                <p><strong>Nama:</strong> {{ $affiliate->name ?: '—' }}</p>
                <p><strong>NPWP:</strong> {{ $affiliate->npwp ?: '—' }}</p>
                <p><strong>License ID:</strong> {{ $affiliate->license_id ?: '—' }}</p>
                <p><strong>Saldo tersedia:</strong> Rp {{ number_format($balance, 0, ',', '.') }}</p>
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
            <div class="card-header"><strong>Komisi terbaru</strong></div>
            <div class="card-body table-responsive p-0">
                <table class="table mb-0">
                    <thead><tr><th>Order</th><th>Jumlah</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        @foreach($affiliate->commissions as $c)
                            <tr>
                                <td>{{ $c->order?->order_code }}</td>
                                <td>Rp {{ number_format($c->amount, 0, ',', '.') }}</td>
                                <td>{{ $c->status }}</td>
                                <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><strong>Klaim</strong></div>
            <div class="card-body table-responsive p-0">
                <table class="table mb-0">
                    <thead><tr><th>ID</th><th>Net</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        @foreach($affiliate->claims as $claim)
                            <tr>
                                <td>#{{ $claim->id }}</td>
                                <td>Rp {{ number_format($claim->net_amount, 0, ',', '.') }}</td>
                                <td>{{ $claim->status }}</td>
                                <td>{{ $claim->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
