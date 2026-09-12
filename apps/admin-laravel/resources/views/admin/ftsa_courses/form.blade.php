@extends('admin.layouts.page')

@section('page_heading', $course->exists ? 'Edit Course FTSA' : 'Tambah Course FTSA')

@section('page_actions')
<a href="{{ route('admin.ftsa-courses.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')
@include('admin.partials.flash')

<form method="POST" action="{{ $course->exists ? route('admin.ftsa-courses.update', $course) : route('admin.ftsa-courses.store') }}">
    @csrf
    @if($course->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <div class="form-group">
                        <label>Nama course <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $course->name) }}" required class="form-control" placeholder="FTSA Batch Acme — Sep 2026">
                    </div>
                    <div class="form-group">
                        <label>Nama perusahaan <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $course->company_name) }}" required class="form-control" placeholder="PT Acme Indonesia">
                    </div>
                    <div class="form-group">
                        <label>Kode daftar</label>
                        <input type="text" name="registration_code" value="{{ old('registration_code', $course->registration_code) }}" class="form-control font-monospace text-uppercase" placeholder="FTSA-ACME-0926">
                        <small class="text-muted">Kosongkan saat tambah baru untuk auto-generate. Peserta daftar di <code>/course/ftsa/daftar/{kode}</code>.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Catatan internal</label>
                        <textarea name="notes" rows="4" class="form-control">{{ old('notes', $course->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <div class="form-group">
                        <label>Tanggal workshop <span class="text-danger">*</span></label>
                        <input type="date" name="workshop_at" value="{{ old('workshop_at', optional($course->workshop_at)->format('Y-m-d')) }}" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Durasi akses (hari)</label>
                        <input type="number" name="access_days" value="{{ old('access_days', $course->access_days ?? 30) }}" min="1" max="365" class="form-control">
                        <small class="text-muted">Default 30 hari dari tanggal workshop. Kode &amp; akses course berakhir setelah itu.</small>
                    </div>
                    <div class="form-group">
                        <label>Max pendaftar</label>
                        <input type="number" name="max_registrants" value="{{ old('max_registrants', $course->max_registrants) }}" min="1" class="form-control" placeholder="Kosong = unlimited">
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" id="ca" class="custom-control-input" {{ old('is_active', $course->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="ca">Aktif</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>
@endsection
