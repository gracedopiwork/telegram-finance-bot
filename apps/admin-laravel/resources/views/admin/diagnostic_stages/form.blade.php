@extends('admin.layouts.page')

@section('page_heading', 'Edit Tahap: '.$stage->label)

@section('page_actions')
<a href="{{ route('admin.diagnostic-stages.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')
<form method="POST" action="{{ route('admin.diagnostic-stages.update', $stage) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Label tahap <span class="text-danger">*</span></label>
                            <input type="text" name="label" class="form-control" value="{{ old('label', $stage->label) }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Emoji</label>
                            <input type="text" name="emoji" class="form-control" value="{{ old('emoji', $stage->emoji) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Fase</label>
                            <input type="text" name="phase" class="form-control" value="{{ old('phase', $stage->phase) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Diagnosis (judul klinis)</label>
                        <input type="text" name="diagnosis" class="form-control" value="{{ old('diagnosis', $stage->diagnosis) }}">
                    </div>
                    <div class="form-group">
                        <label>Label risiko (contoh: Risiko keuangan)</label>
                        <input type="text" name="risk_label" class="form-control" value="{{ old('risk_label', $stage->risk_label) }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>Deskripsi risiko (tampil di hasil landing)</label>
                        <textarea name="risk_description" rows="4" class="form-control">{{ old('risk_description', $stage->risk_description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <div class="form-group">
                        <label>Warna panel kiri</label>
                        <input type="color" name="panel_color" class="form-control" value="{{ old('panel_color', $stage->panel_color ?: '#7EC8C8') }}">
                    </div>
                    <div class="form-group">
                        <label>Upload ilustrasi (kanan)</label>
                        <input type="file" name="illustration_file" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,image/*">
                        <small class="text-muted d-block">Maks 5MB. Format: JPG, PNG, WEBP.</small>
                    </div>
                    @if(!empty($stage->illustration_url))
                        <div class="form-group">
                            <label>Preview ilustrasi saat ini</label>
                            <div class="border rounded p-2 text-center bg-light">
                                <img src="{{ $stage->illustration_url }}" alt="{{ $stage->label }}" style="max-height: 180px; max-width: 100%; object-fit: contain;">
                            </div>
                        </div>
                    @endif
                    <div class="form-group">
                        <label>URL ilustrasi (kanan)</label>
                        <input type="url" name="illustration_url" class="form-control" value="{{ old('illustration_url', $stage->illustration_url) }}" placeholder="https://...">
                        <small class="text-muted">Opsional manual URL. Jika upload file di atas, URL ini akan otomatis terisi path file baru.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>Skor min</label>
                            <input type="number" name="score_min" class="form-control" value="{{ old('score_min', $stage->score_min) }}" required>
                        </div>
                        <div class="form-group col-6">
                            <label>Skor max</label>
                            <input type="number" name="score_max" class="form-control" value="{{ old('score_max', $stage->score_max) }}" required>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>
@endsection
