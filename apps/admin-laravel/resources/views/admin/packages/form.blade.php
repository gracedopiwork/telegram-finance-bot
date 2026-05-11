@extends('admin.layouts.page')

@section('page_heading', $package->exists ? 'Edit Paket' : 'Tambah Paket')
@section('page_subheading', $package->exists ? $package->name : 'Paket baru')

@section('page_actions')
<a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')

<form method="POST"
      action="{{ $package->exists ? route('admin.packages.update', $package) : route('admin.packages.store') }}">
    @csrf
    @if($package->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Informasi Dasar</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" value="{{ old('code', $package->code) }}" required class="form-control font-monospace"
                                   placeholder="lite, pro, ecosystem">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tier Label</label>
                            <input type="text" name="tier_label" value="{{ old('tier_label', $package->tier_label) }}" class="form-control" placeholder="LITE">
                        </div>
                        <div class="form-group col-12">
                            <label>Nama Paket <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $package->name) }}" required class="form-control">
                        </div>
                        <div class="form-group col-12">
                            <label>Deskripsi</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $package->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Fitur (satu per baris)</h3></div>
                <div class="card-body">
                    <textarea name="features_text" rows="10" class="form-control font-monospace"
                              placeholder="Satu fitur per baris">{{ old('features_text', is_array($package->features) ? implode("\n", $package->features) : '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Harga</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Harga (IDR) <span class="text-danger">*</span></label>
                        <input type="number" name="price" value="{{ old('price', $package->price) }}" required min="0" class="form-control">
                    </div>
                    <div class="form-group mb-0">
                        <label>Periode</label>
                        <input type="text" name="period" value="{{ old('period', $package->period ?? '/paket') }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Pengaturan</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Variant</label>
                        <select name="variant" class="form-control">
                            <option value="plain" {{ ($package->variant ?? 'plain') === 'plain' ? 'selected' : '' }}>Plain</option>
                            <option value="featured" {{ ($package->variant ?? '') === 'featured' ? 'selected' : '' }}>Featured</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Urutan (sort)</label>
                        <input type="number" name="sort" value="{{ old('sort', $package->sort ?? 0) }}" class="form-control">
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" name="is_recommended" value="1" id="rec" class="custom-control-input" {{ $package->is_recommended ? 'checked' : '' }}>
                        <label class="custom-control-label" for="rec">Recommended</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" id="act" class="custom-control-input" {{ ($package->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="act">Aktif</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> {{ $package->exists ? 'Simpan' : 'Buat Paket' }}</button>
        </div>
    </div>
</form>

@endsection
