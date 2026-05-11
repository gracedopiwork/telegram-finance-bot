@extends('admin.layouts.page')

@section('page_heading', 'FAQ')
@section('page_subheading', 'Pertanyaan di halaman Informasi')

@section('page_actions')
<a href="{{ route('admin.faqs.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah FAQ</a>
@endsection

@section('main')

<div class="card card-outline card-success">
    <div class="card-body">
        <table id="faqs-table" class="table table-hover" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Pertanyaan</th>
                    <th>Kategori</th>
                    <th class="text-center">Urutan</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faqs as $f)
                    <tr>
                        <td><strong>{{ Str::limit($f->question, 80) }}</strong></td>
                        <td>{{ $f->category ?? '—' }}</td>
                        <td class="text-center">{{ $f->sort }}</td>
                        <td class="text-center">@if($f->is_active)<span class="badge badge-success">Aktif</span>@else<span class="badge badge-secondary">Off</span>@endif</td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.faqs.edit', $f) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            @include('admin.partials.delete-form', ['action' => route('admin.faqs.destroy', $f)])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada FAQ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('admin_js')
<script>
$(function() {
    $('#faqs-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        "columnDefs": [{ "orderable": false, "targets": [-1] }],
        "order": [[2, "asc"]],
        "pageLength": 25
    });
});
</script>
@stop
