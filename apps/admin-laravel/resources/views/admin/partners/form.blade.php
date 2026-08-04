@extends('admin.layouts.page')

@section('page_heading', $partner->exists ? 'Edit Partner' : 'Tambah Partner')

@section('page_actions')
<a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')

<form method="POST" action="{{ $partner->exists ? route('admin.partners.update', $partner) : route('admin.partners.store') }}">
    @csrf
    @if($partner->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success">
                <div class="card-body">
                    <div class="form-group">
                        <label>Nama partner <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $partner->title) }}" required class="form-control" placeholder="Contoh: Legal / Notaris">
                    </div>
                    <div class="form-group mb-0">
                        <label>Deskripsi</label>
                        <textarea name="description" rows="5" class="form-control" placeholder="Ditampilkan di halaman Penasihat">{{ old('description', $partner->description) }}</textarea>
                        <small class="text-muted">Halaman Layanan hanya menampilkan nama; deskripsi dipakai di halaman Penasihat.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <div class="form-group">
                        <label>Icon Material Symbols</label>
                        <input type="text" name="icon" value="{{ old('icon', $partner->icon ?? 'handshake') }}" class="form-control font-monospace" placeholder="gavel">
                        <small class="text-muted">Contoh: <code>gavel</code>, <code>balance</code>, <code>handshake</code>. Lihat <a href="https://fonts.google.com/icons" target="_blank" rel="noopener">Google Icons</a>.</small>
                    </div>
                    <div class="form-group">
                        <label>Urutan</label>
                        <input type="number" name="sort" value="{{ old('sort', $partner->sort ?? 0) }}" class="form-control">
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" id="pa" class="custom-control-input" {{ old('is_active', $partner->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="pa">Aktif</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>

@endsection
