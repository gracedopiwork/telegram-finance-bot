@extends('admin.layouts.page')

@section('page_heading', 'Soal FTSA')
@section('page_subheading', 'Kuesioner FTSA 1–32 — skala likert 1–5 di portal baseline')

@section('page_actions')
<form method="POST" action="{{ route('admin.ftsa-questions.sync') }}" class="d-inline mr-1">
    @csrf
    <button type="submit" class="btn btn-outline-secondary btn-sm"
            onclick="return confirm('Timpa soal FTSA di database dengan isi config baseline_assessment.php?')">
        <i class="fas fa-sync mr-1"></i> Sync dari Config
    </button>
</form>
<a href="{{ route('admin.ftsa-results.index') }}" class="btn btn-outline-primary btn-sm mr-1">
    <i class="fas fa-brain mr-1"></i> Hasil FTSA
</a>
<a href="{{ route('admin.diagnostic-questions.index') }}" class="btn btn-outline-info btn-sm">
    <i class="fas fa-clipboard-list mr-1"></i> Soal Diagnostik
</a>
@endsection

@section('main')
@if(!$schemaReady)
    <div class="alert alert-warning">
        Tabel <code>ftsa_questions</code> belum siap. Jalankan <code>php artisan migrate --force</code>
        lalu <code>php artisan diagnostic:sync-questions</code>.
    </div>
@endif

<div class="alert alert-info py-2">
    <i class="fas fa-info-circle mr-1"></i>
    Skala jawaban likert (1–5) tetap dari config. Yang bisa diubah di sini: <strong>teks pertanyaan</strong>, domain (CHD/RVD/SSD/ESD), dan status aktif.
</div>

<div class="card card-outline card-primary">
    <div class="card-body">
        <table id="ftsa-q-table" class="table table-hover table-sm" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th class="text-center" width="60">No</th>
                    <th width="90">Domain</th>
                    <th>Pertanyaan</th>
                    <th class="text-center" width="70">Urutan</th>
                    <th class="text-center" width="80">Status</th>
                    <th class="text-right" width="80">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $q)
                    @php $meta = $q->domainMeta(); @endphp
                    <tr>
                        <td class="text-center font-weight-bold">{{ $q->question_num }}</td>
                        <td>
                            <span class="badge badge-info">{{ $meta['code'] }}</span>
                            <div class="text-muted small">{{ $meta['label'] }}</div>
                        </td>
                        <td>{{ $q->text }}</td>
                        <td class="text-center">{{ $q->sort_order }}</td>
                        <td class="text-center">
                            @if($q->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Off</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.ftsa-questions.edit', $q) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Belum ada soal FTSA. Klik <strong>Sync dari Config</strong> atau jalankan
                            <code>php artisan diagnostic:sync-questions</code>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('admin_js')
<script>
$(function() {
    $('#ftsa-q-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order: [[0, 'asc']],
        pageLength: 32
    });
});
</script>
@stop
