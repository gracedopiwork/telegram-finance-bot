@extends('admin.layouts.page')

@section('page_heading', 'Tim Dokter')
@section('page_subheading', 'Founder & penasihat YFD')

@section('page_actions')
<a href="{{ route('admin.advisors.create') }}" class="btn btn-success btn-sm"><i class="fas fa-user-plus mr-1"></i> Tambah</a>
@endsection

@section('main')

<div class="row">
    @forelse($advisors as $a)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-outline card-success h-100">
                <div class="position-relative" style="height: 200px; overflow: hidden; background: #f4f6f9;">
                    @if($a->photo_path)
                        <img src="{{ $a->photo_url }}" alt="{{ $a->name }}" class="w-100 h-100" style="object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="fas fa-user fa-4x"></i></div>
                    @endif
                    @if($a->tag)<span class="badge badge-warning position-absolute" style="top:8px;right:8px">{{ $a->tag }}</span>@endif
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-1">{{ $a->name }}</h5>
                    <p class="text-muted small mb-2">{{ $a->role_label }}</p>
                    @if(is_array($a->badges))
                        @foreach($a->badges as $b)<span class="badge badge-light border mr-1">{{ $b }}</span>@endforeach
                    @endif
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.advisors.edit', $a) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    @include('admin.partials.delete-form', ['action' => route('admin.advisors.destroy', $a)])
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><p class="text-muted">Belum ada data.</p></div>
    @endforelse
</div>

@endsection
