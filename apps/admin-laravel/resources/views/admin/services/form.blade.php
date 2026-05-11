@extends('admin.layouts.page')

@section('page_heading', $service->exists ? 'Edit Layanan' : 'Tambah Layanan')
@section('page_subheading', $service->title ?? '')

@section('page_actions')
<a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')

<form method="POST" enctype="multipart/form-data"
      action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}">
    @csrf
    @if($service->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Konten</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Eyebrow</label>
                            <input type="text" name="eyebrow" value="{{ old('eyebrow', $service->eyebrow) }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Section</label>
                            <input type="text" name="section" value="{{ old('section', $service->section ?? 'main') }}" class="form-control">
                        </div>
                        <div class="form-group col-12">
                            <label>Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $service->title) }}" required class="form-control">
                        </div>
                        <div class="form-group col-12">
                            <label>Deskripsi</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $service->description) }}</textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Icon (Material Symbols)</label>
                            <input type="text" name="icon" value="{{ old('icon', $service->icon) }}" class="form-control font-monospace" placeholder="monitor_heart">
                        </div>
                        <div class="form-group col-md-6">
                            <label>CTA Label</label>
                            <input type="text" name="cta_label" value="{{ old('cta_label', $service->cta_label) }}" class="form-control">
                        </div>
                        <div class="form-group col-12">
                            <label>CTA Route (nama route Laravel)</label>
                            <input type="text" name="cta_route" value="{{ old('cta_route', $service->cta_route) }}" class="form-control font-monospace" placeholder="company.paket">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Fitur (satu per baris)</h3></div>
                <div class="card-body">
                    <textarea name="features_text" rows="8" class="form-control font-monospace">{{ old('features_text', is_array($service->features) ? implode("\n", $service->features) : '') }}</textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-info mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Gambar</h3></div>
                <div class="card-body">
                    @if($service->image_path)
                        <img src="{{ $service->image_url }}" alt="" class="img-fluid rounded mb-2">
                    @endif
                    <input type="file" name="image" accept="image/*" class="form-control-file">
                    <small class="text-muted">Opsional, max 2MB</small>
                </div>
            </div>
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Urutan</h3></div>
                <div class="card-body">
                    <input type="number" name="sort" value="{{ old('sort', $service->sort ?? 0) }}" class="form-control">
                    <div class="custom-control custom-checkbox mt-3">
                        <input type="checkbox" name="is_active" value="1" id="sa" class="custom-control-input" {{ ($service->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="sa">Aktif</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>

@endsection
