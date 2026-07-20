@extends('admin.layouts.page')

@section('page_heading', 'Pilih Lokasi Google Business')
@section('page_subheading', 'Akun punya lebih dari satu lokasi — pilih yang untuk Your Financial Doctor')

@section('main')
<div class="card card-outline card-success">
    <div class="card-body">
        <p class="text-muted mb-3">
            Account: <strong>{{ $pending['account_label'] ?? $pending['account_name'] }}</strong>
        </p>
        <form method="POST" action="{{ route('admin.google-reviews.pick-location.store') }}">
            @csrf
            @foreach($pending['locations'] as $loc)
                <div class="custom-control custom-radio mb-3">
                    <input type="radio" id="loc_{{ md5($loc['name']) }}" name="location_name"
                           value="{{ $loc['name'] }}" class="custom-control-input" required>
                    <label class="custom-control-label" for="loc_{{ md5($loc['name']) }}">
                        <strong>{{ $loc['title'] }}</strong>
                        @if(!empty($loc['account_label']))
                            <span class="text-muted">· {{ $loc['account_label'] }}</span>
                        @endif
                        <small class="text-muted d-block font-monospace">{{ $loc['name'] }}</small>
                    </label>
                </div>
            @endforeach
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check mr-1"></i> Simpan & Sync Ulasan
            </button>
            <a href="{{ route('admin.settings.index', ['group' => 'reviews']) }}" class="btn btn-outline-secondary ml-2">Batal</a>
        </form>
    </div>
</div>
@endsection
