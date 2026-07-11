@extends('admin.layouts.page')

@section('page_heading', $article->exists ? 'Edit Artikel' : 'Artikel Baru')

@section('page_actions')
<a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')

<form method="POST" enctype="multipart/form-data"
      action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}">
    @csrf
    @if($article->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-body">
                    <div class="form-group">
                        <label>Judul <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $article->title) }}" required class="form-control form-control-lg font-weight-bold">
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $article->slug) }}" class="form-control font-monospace" placeholder="auto dari judul">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi / excerpt</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $article->description) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Konten (HTML)</label>
                        <textarea name="content_html" rows="18" class="form-control font-monospace small">{{ old('content_html', $article->content_html) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-info mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Thumbnail</h3></div>
                <div class="card-body">
                    @if($article->image_path)
                        <img src="{{ $article->image_url }}" alt="" class="img-fluid rounded mb-2">
                    @endif
                    <input type="file" name="image" accept="image/*" class="form-control-file">
                </div>
            </div>
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <div class="form-group">
                        <label>Kategori</label>
                        @php
                            $existingCategories = \App\Models\CpArticle::query()
                                ->whereNotNull('category')
                                ->where('category', '!=', '')
                                ->distinct()
                                ->orderBy('category')
                                ->pluck('category');
                        @endphp
                        <input type="text" name="category" list="article-categories"
                               value="{{ old('category', $article->category) }}" class="form-control"
                               placeholder="Contoh: Emotional Finance, Cashflow, Investasi">
                        <datalist id="article-categories">
                            @foreach($existingCategories as $cat)
                                <option value="{{ $cat }}"></option>
                            @endforeach
                        </datalist>
                        <small class="form-text text-muted">
                            Diisi manual (bukan otomatis). Kategori ini muncul di Wealthpedia sebagai filter/pool artikel.
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Read time</label>
                        <input type="text" name="read_time" value="{{ old('read_time', $article->read_time) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Views label</label>
                        <input type="text" name="views_label" value="{{ old('views_label', $article->views_label) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Sort</label>
                        <input type="number" name="sort" value="{{ old('sort', $article->sort ?? 0) }}" class="form-control">
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" id="pub" class="custom-control-input" {{ ($article->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="pub">Publish</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>

@endsection
