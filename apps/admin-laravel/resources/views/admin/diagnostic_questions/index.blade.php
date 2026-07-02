@extends('admin.layouts.page')

@section('page_heading', 'Soal Diagnostik')
@section('page_subheading', 'Financial Health Check-Up — soal, jawaban, dan catatan')

@section('page_actions')
<a href="{{ route('admin.diagnostic-results.index') }}" class="btn btn-outline-primary btn-sm mr-1">
    <i class="fas fa-poll mr-1"></i> Hasil Diagnostik
</a>
<a href="{{ route('admin.diagnostic-stages.index') }}" class="btn btn-outline-info btn-sm mr-1">
    <i class="fas fa-palette mr-1"></i> Tahap Hasil
</a>
<a href="{{ route('admin.diagnostic-questions.create') }}" class="btn btn-success btn-sm">
    <i class="fas fa-plus mr-1"></i> Tambah Soal
</a>
@endsection

@section('main')
<div class="card card-outline card-success">
    <div class="card-body">
        <table id="diag-table" class="table table-hover" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Langkah</th>
                    <th>Kode</th>
                    <th>Soal</th>
                    <th>Seksi</th>
                    <th class="text-center">Skor</th>
                    <th class="text-center">Opsi</th>
                    <th class="text-center">Urutan</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $q)
                    <tr>
                        <td class="text-center"><span class="badge badge-primary">{{ $q->wizard_step }}</span></td>
                        <td><code>{{ $q->question_key }}</code></td>
                        <td>
                            <strong>{{ Str::limit($q->text, 70) }}</strong>
                            @if($q->note)
                                <div class="text-muted small mt-1"><i class="fas fa-sticky-note"></i> {{ Str::limit($q->note, 60) }}</div>
                            @endif
                        </td>
                        <td>{{ $q->section }}</td>
                        <td class="text-center">@if($q->is_scored)<span class="badge badge-warning">Ya</span>@else<span class="badge badge-light">Profil</span>@endif</td>
                        <td class="text-center">{{ $q->options_count }}</td>
                        <td class="text-center">{{ $q->sort_order }}</td>
                        <td class="text-center">@if($q->is_active)<span class="badge badge-success">Aktif</span>@else<span class="badge badge-secondary">Off</span>@endif</td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.diagnostic-questions.edit', $q) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            @include('admin.partials.delete-form', ['action' => route('admin.diagnostic-questions.destroy', $q)])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada soal. Jalankan <code>php artisan diagnostic:sync-questions</code> atau tambah manual.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('admin_js')
<script>
$(function() {
    $('#diag-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order: [[0, 'asc'], [6, 'asc']],
        pageLength: 25
    });
});
</script>
@stop
