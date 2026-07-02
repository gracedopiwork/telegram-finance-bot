@extends('admin.layouts.page')

@section('page_heading', 'Pemetaan Kategori → Bucket')
@section('page_subheading', 'Acuan AI & dashboard — definisi bucket YFD First Aid')

@section('page_actions')
<form method="post" action="{{ route('admin.category-bucket-mappings.sync') }}" class="d-inline"
      onsubmit="return confirm('Sinkronkan data default? Baris dengan kategori sama akan diperbarui.');">
    @csrf
    <button type="submit" class="btn btn-outline-info btn-sm mr-1">
        <i class="fas fa-sync mr-1"></i> Sync Default
    </button>
</form>
<a href="{{ route('admin.category-bucket-mappings.create') }}" class="btn btn-success btn-sm">
    <i class="fas fa-plus mr-1"></i> Tambah Pemetaan
</a>
@endsection

@section('main')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-success">
            <div class="card-body">
                <table id="bucket-map-table" class="table table-hover table-sm" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Kategori</th>
                            <th>Sub</th>
                            <th>Bucket</th>
                            <th>Tipe</th>
                            <th>Sifat</th>
                            <th class="text-center">Urut</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings as $m)
                            <tr>
                                <td>
                                    <strong>{{ $m->category }}</strong>
                                    @if($m->reason)
                                        <div class="text-muted small">{{ Str::limit($m->reason, 55) }}</div>
                                    @endif
                                </td>
                                <td>{{ $m->sub_category ?: '—' }}</td>
                                <td><span class="badge badge-primary">{{ $m->bucket }}</span></td>
                                <td>{{ \App\Models\CategoryBucketMapping::TRANSACTION_TYPES[$m->transaction_type] ?? $m->transaction_type }}</td>
                                <td>{{ $m->nature ?: '—' }}</td>
                                <td class="text-center">{{ $m->sort_order }}</td>
                                <td class="text-center">
                                    @if($m->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Off</span>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.category-bucket-mappings.edit', $m) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    @include('admin.partials.delete-form', ['action' => route('admin.category-bucket-mappings.destroy', $m)])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada pemetaan. Klik <strong>Sync Default</strong> untuk memuat template resmi YFD.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title mb-0">Definisi Bucket (resmi)</h3></div>
            <div class="card-body small">
                <p><strong>Essential Living</strong> — kebutuhan dasar (makan, transport, tagihan). Target <em>maksimum</em>; semakin rendah % dari income, semakin sehat.</p>
                <p><strong>Future Building</strong> — investasi & pertumbuhan. Target <em>minimum</em>; semakin tinggi semakin sehat.</p>
                <p><strong>Protection</strong> — asuransi, dana darurat, utilitas protektif. Target maksimum sesuai tahap.</p>
                <p><strong>Flexible + Social</strong> — jajan, hiburan, hadiah, sosial. Target maksimum — jangan melebihi ideal tahap finansial.</p>
                <p class="mb-0"><strong>Income</strong> & <strong>Transfer</strong> tidak masuk perhitungan 4-bucket pengeluaran.</p>
            </div>
        </div>
        <div class="card card-outline card-secondary">
            <div class="card-body small text-muted">
                <p class="mb-1"><i class="fas fa-info-circle"></i> Urutan baris = prioritas pencocokan (atas lebih dulu).</p>
                <p class="mb-0">Gunakan kategori <code>*</code> + sifat untuk aturan global (mis. semua <em>Wants</em>).</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('admin_js')
<script>
$(function() {
    $('#bucket-map-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order: [[5, 'asc']],
        pageLength: 50
    });
});
</script>
@stop
