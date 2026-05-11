@extends('admin.layouts.page')

@section('page_heading', 'Wealthpedia')
@section('page_subheading', 'Artikel edukasi finansial')

@section('page_actions')
<a href="{{ route('admin.articles.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Artikel Baru</a>
@endsection

@section('main')

<div class="row">
    @forelse($articles as $a)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-outline card-success h-100">
                <div style="height: 160px; overflow: hidden; background: #f4f6f9;">
                    @if($a->image_path)
                        <img src="{{ $a->image_url }}" alt="" class="w-100 h-100" style="object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="fas fa-newspaper fa-3x"></i></div>
                    @endif
                </div>
                <div class="card-body">
                    @if($a->category)<span class="badge badge-info">{{ $a->category }}</span>@endif
                    <h5 class="mt-2 mb-1">{{ Str::limit($a->title, 60) }}</h5>
                    <small class="text-muted d-block">/{{ $a->slug }}</small>
                    @if(!$a->is_active)<span class="badge badge-secondary">Draft</span>@endif
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.articles.edit', $a) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    @include('admin.partials.delete-form', ['action' => route('admin.articles.destroy', $a)])
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><p class="text-muted">Belum ada artikel.</p></div>
    @endforelse
</div>

@endsection
