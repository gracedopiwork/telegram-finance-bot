@extends('admin.layouts.page')

@section('page_heading', 'Edit Soal FTSA #'.$question->question_num)

@section('page_actions')
<a href="{{ route('admin.ftsa-questions.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
</a>
@endsection

@section('main')
@php $meta = $question->domainMeta(); @endphp

<form method="POST" action="{{ route('admin.ftsa-questions.update', $question) }}">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header"><strong>Soal #{{ $question->question_num }}</strong></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Nomor soal</label>
                            <input type="text" class="form-control" value="{{ $question->question_num }}" disabled>
                            <small class="text-muted">Nomor 1–32 tetap (untuk skor domain).</small>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Domain FTSA <span class="text-danger">*</span></label>
                            <select name="domain_key" class="form-control" required>
                                @foreach($domainOptions as $opt)
                                    <option value="{{ $opt['value'] }}" @selected(old('domain_key', $question->domain_key) === $opt['value'])>
                                        {{ $opt['code'] }} — {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Teks pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="text" rows="4" class="form-control" required>{{ old('text', $question->text) }}</textarea>
                        <small class="text-muted">Ditampilkan di portal Baseline Data (skala 1–5).</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header"><strong>Pengaturan</strong></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Urutan tampil</label>
                        <input type="number" name="sort_order" class="form-control" min="0" max="999"
                               value="{{ old('sort_order', $question->sort_order) }}">
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $question->is_active))>
                        <label class="custom-control-label" for="is_active">Aktif di portal</label>
                    </div>
                    <hr>
                    <p class="small text-muted mb-0">
                        Archetype domain ini: <strong>{{ $meta['archetype_label'] ?: '—' }}</strong>
                    </p>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-save mr-1"></i> Simpan Perubahan
            </button>
        </div>
    </div>
</form>
@endsection
