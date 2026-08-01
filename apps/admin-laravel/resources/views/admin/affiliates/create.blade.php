@extends('adminlte::page')

@section('title', 'Tambah Affiliate')

@section('content_header')
    <h1>Tambah Affiliate dari User Existing</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><strong>Data user yang sudah ada</strong></div>
            <form method="POST" action="{{ route('admin.affiliates.store') }}">
                @csrf
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

                    @if($candidates->isNotEmpty())
                        <div class="form-group">
                            <label>Pilih dari order lunas (belum punya affiliate)</label>
                            <select id="candidate_select" class="form-control">
                                <option value="">— ketik email manual di bawah —</option>
                                @foreach($candidates as $c)
                                    <option
                                        value="{{ $c->email }}"
                                        data-name="{{ $c->full_name }}"
                                    >
                                        {{ $c->full_name }} · {{ $c->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group">
                        <label>Email user <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="affiliate_email" value="{{ old('email') }}"
                               class="form-control" required maxlength="190"
                               placeholder="harus sudah ada di order">
                        <small class="text-muted">Email harus terdaftar di order (lunas/pending).</small>
                    </div>

                    <div class="form-group">
                        <label>Nama (opsional)</label>
                        <input type="text" name="name" id="affiliate_name" value="{{ old('name') }}"
                               class="form-control" maxlength="120"
                               placeholder="Kosongkan = pakai nama dari order">
                    </div>

                    <div class="form-group">
                        <label>Kode affiliate (opsional)</label>
                        <input type="text" name="referral_code" value="{{ old('referral_code') }}"
                               class="form-control text-uppercase" maxlength="32"
                               placeholder="Contoh: YFD-VICTORIA — kosongkan = otomatis">
                        <small class="text-muted">Huruf/angka/tanda -. Kalau email sudah punya affiliate, kode baru akan mengganti yang lama.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-handshake mr-1"></i> Simpan Affiliate
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-outline card-info">
            <div class="card-body small">
                <p class="mb-2"><strong>Cara pakai</strong></p>
                <ul class="pl-3 mb-0">
                    <li>Pilih user dari daftar, atau ketik email order yang sudah ada.</li>
                    <li>Isi kode custom, atau kosongkan agar di-generate otomatis.</li>
                    <li>Bisa juga atur kode langsung dari <strong>Detail Order</strong>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
(function () {
    var select = document.getElementById('candidate_select');
    if (!select) return;
    select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;
        document.getElementById('affiliate_email').value = opt.value;
        document.getElementById('affiliate_name').value = opt.getAttribute('data-name') || '';
    });
})();
</script>
@stop
