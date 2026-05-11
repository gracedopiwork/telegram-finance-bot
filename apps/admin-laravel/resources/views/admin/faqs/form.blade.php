@extends('admin.layouts.page')

@section('page_heading', $faq->exists ? 'Edit FAQ' : 'Tambah FAQ')

@section('page_actions')
<a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')

<form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}">
    @csrf
    @if($faq->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success">
                <div class="card-body">
                    <div class="form-group">
                        <label>Pertanyaan <span class="text-danger">*</span></label>
                        <input type="text" name="question" value="{{ old('question', $faq->question) }}" required class="form-control">
                    </div>
                    <div class="form-group mb-0">
                        <label>Jawaban <span class="text-danger">*</span></label>
                        <textarea name="answer" rows="10" required class="form-control">{{ old('answer', $faq->answer) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <div class="form-group">
                        <label>Kategori</label>
                        <input type="text" name="category" value="{{ old('category', $faq->category) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Urutan</label>
                        <input type="number" name="sort" value="{{ old('sort', $faq->sort ?? 0) }}" class="form-control">
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" id="fq" class="custom-control-input" {{ ($faq->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="fq">Aktif</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>

@endsection
