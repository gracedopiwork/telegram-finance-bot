@extends('admin.layouts.page')

@section('page_heading', 'Tahap Hasil Check-Up')
@section('page_subheading', 'Label, warna panel, risiko keuangan, dan ilustrasi per tahap')

@section('page_actions')
<a href="{{ route('admin.diagnostic-questions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Soal Diagnostik</a>
@endsection

@section('main')
<div class="row">
    @foreach($stages as $stage)
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100" style="border-top: 6px solid {{ $stage->panel_color }}">
                <div class="card-body">
                    <div class="text-center mb-2" style="font-size:2rem">{{ $stage->emoji }}</div>
                    <h5 class="text-center font-weight-bold">{{ $stage->label }}</h5>
                    <p class="text-muted text-center small mb-2">Skor {{ $stage->score_min }}–{{ $stage->score_max }}</p>
                    <p class="small text-muted">{{ Str::limit($stage->risk_description, 90) }}</p>
                    <a href="{{ route('admin.diagnostic-stages.edit', $stage) }}" class="btn btn-outline-primary btn-sm btn-block mt-2">
                        <i class="fas fa-edit"></i> Edit tampilan
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
