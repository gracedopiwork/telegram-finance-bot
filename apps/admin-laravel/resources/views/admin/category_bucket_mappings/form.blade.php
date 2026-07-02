@extends('admin.layouts.page')

@section('page_heading', $mapping->exists ? 'Edit Pemetaan Bucket' : 'Tambah Pemetaan Bucket')

@section('page_actions')
<a href="{{ route('admin.category-bucket-mappings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')
<form method="POST" action="{{ $mapping->exists ? route('admin.category-bucket-mappings.update', $mapping) : route('admin.category-bucket-mappings.store') }}">
    @csrf
    @if($mapping->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" class="form-control" required
                                   value="{{ old('category', $mapping->category) }}"
                                   placeholder="Makan, Gaji, Jajan, atau * untuk wildcard sifat">
                            <small class="text-muted">Sesuai kategori bot / sheet (case tidak sensitif).</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sub-kategori</label>
                            <input type="text" name="sub_category" class="form-control"
                                   value="{{ old('sub_category', $mapping->sub_category) }}"
                                   placeholder="Opsional — kosongkan = semua sub">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Bucket <span class="text-danger">*</span></label>
                            <select name="bucket" class="form-control" required>
                                @foreach($buckets as $bucket)
                                    <option value="{{ $bucket }}" @selected(old('bucket', $mapping->bucket) === $bucket)>{{ $bucket }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tipe transaksi <span class="text-danger">*</span></label>
                            <select name="transaction_type" class="form-control" required>
                                @foreach($transactionTypes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('transaction_type', $mapping->transaction_type) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Sifat (opsional)</label>
                            <select name="nature" class="form-control">
                                @foreach($natures as $nature)
                                    <option value="{{ $nature }}" @selected(old('nature', $mapping->nature ?? '') === $nature)>
                                        {{ $nature === '' ? '— Semua sifat —' : $nature }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Urutan</label>
                            <input type="number" name="sort_order" class="form-control" min="0" max="9999"
                                   value="{{ old('sort_order', $mapping->sort_order ?? 0) }}">
                        </div>
                        <div class="form-group col-md-3 d-flex align-items-end">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                                       @checked(old('is_active', $mapping->is_active ?? true))>
                                <label class="custom-control-label" for="is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Kata kunci tambahan</label>
                        <textarea name="match_keywords" rows="2" class="form-control"
                                  placeholder="Pisahkan dengan koma: kopi, jajan, liburan">{{ old('match_keywords', $mapping->match_keywords) }}</textarea>
                        <small class="text-muted">Dicocokkan ke sub-kategori + keterangan transaksi.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Alasan / konteks (dokumentasi)</label>
                        <textarea name="reason" rows="3" class="form-control"
                                  placeholder="Contoh: Kebutuhan dasar harian → Essential Living">{{ old('reason', $mapping->reason) }}</textarea>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>
@endsection
