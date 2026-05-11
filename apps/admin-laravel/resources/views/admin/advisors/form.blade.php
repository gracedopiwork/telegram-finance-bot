@extends('admin.layouts.page')

@section('page_heading', $advisor->exists ? 'Edit Tim Dokter' : 'Tambah Tim Dokter')

@section('page_actions')
<a href="{{ route('admin.advisors.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')

<form method="POST" enctype="multipart/form-data"
      action="{{ $advisor->exists ? route('admin.advisors.update', $advisor) : route('admin.advisors.store') }}">
    @csrf
    @if($advisor->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Informasi</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Nama <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $advisor->name) }}" required class="form-control">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Role / Jabatan</label>
                            <input type="text" name="role_label" value="{{ old('role_label', $advisor->role_label) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tag</label>
                            <input type="text" name="tag" value="{{ old('tag', $advisor->tag) }}" class="form-control" placeholder="Founder">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tahun / Since</label>
                            <input type="text" name="years_exp" value="{{ old('years_exp', $advisor->years_exp) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Icon (Material Symbols)</label>
                            <input type="text" name="spec_icon" value="{{ old('spec_icon', $advisor->spec_icon) }}" class="form-control font-monospace">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Specialty singkat</label>
                        <input type="text" name="spec_short" value="{{ old('spec_short', $advisor->spec_short) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Bio panjang</label>
                        <textarea name="spec_long" rows="5" class="form-control">{{ old('spec_long', $advisor->spec_long) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Badges (satu per baris)</label>
                        <textarea name="badges_text" rows="3" class="form-control">{{ old('badges_text', is_array($advisor->badges) ? implode("\n", $advisor->badges) : '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-info mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Foto</h3></div>
                <div class="card-body">
                    @if($advisor->photo_path)
                        <img src="{{ $advisor->photo_url }}" alt="" class="img-fluid rounded mb-2">
                    @endif
                    <input type="file" name="photo" accept="image/*" class="form-control-file">
                </div>
            </div>
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <label>Sort</label>
                    <input type="number" name="sort" value="{{ old('sort', $advisor->sort ?? 0) }}" class="form-control">
                    <div class="custom-control custom-checkbox mt-3">
                        <input type="checkbox" name="is_active" value="1" id="aa" class="custom-control-input" {{ ($advisor->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="aa">Aktif</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>

@endsection
