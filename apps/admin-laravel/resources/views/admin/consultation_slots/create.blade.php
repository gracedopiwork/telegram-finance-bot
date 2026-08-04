@extends('admin.layouts.page')

@section('page_heading', 'Tambah Slot Jadwal')
@section('page_subheading', 'Satu tanggal + beberapa jam mulai (satu baris / dipisah koma)')

@section('page_actions')
<a href="{{ route('admin.consultation-slots.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')

<form method="POST" action="{{ route('admin.consultation-slots.store') }}">
    @csrf
    <div class="row">
        <div class="col-lg-7">
            <div class="card card-outline card-success">
                <div class="card-body">
                    <div class="form-group">
                        <label>Dokter <span class="text-danger">*</span></label>
                        <select name="advisor_id" required class="form-control">
                            <option value="">— pilih —</option>
                            @foreach($advisors as $a)
                                <option value="{{ $a->id }}" {{ old('advisor_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                        @error('advisor_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" value="{{ old('date') }}" required class="form-control">
                        @error('date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Jam mulai (satu baris per slot) <span class="text-danger">*</span></label>
                        <textarea name="times" rows="8" required class="form-control font-monospace" placeholder="09:00&#10;10:00&#10;14:00&#10;15:30">{{ old('times', "09:00\n10:00\n14:00\n15:00") }}</textarea>
                        <small class="text-muted">Contoh: 09:00, 10:00. Slot yang bentrok dengan jadwal existing dilewati.</small>
                        @error('times')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label>Durasi (menit)</label>
                        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="15" max="240" class="form-control" style="max-width:140px;">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <p class="mb-2"><strong>Alur:</strong></p>
                    <ol class="pl-3 mb-0 small text-muted">
                        <li>Admin input slot available</li>
                        <li>User pilih di /pertemuan → hold 45 menit</li>
                        <li>User lanjut WA</li>
                        <li>Admin konfirmasi bayar → slot locked</li>
                    </ol>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan Slot</button>
        </div>
    </div>
</form>

@endsection
