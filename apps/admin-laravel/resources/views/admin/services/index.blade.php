@extends('admin.layouts.page')

@section('page_heading', 'Layanan YFD')
@section('page_subheading', 'Enam pilar layanan di website')

@section('page_actions')
<a href="{{ route('admin.services.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Layanan</a>
@endsection

@section('main')

<div class="card card-outline card-success">
    <div class="card-body">
        <table id="services-table" class="table table-hover table-striped" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Layanan</th>
                    <th>Eyebrow</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $idx => $s)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $s->title }}</strong><br><small class="text-muted">{{ Str::limit($s->description, 70) }}</small></td>
                        <td><small class="text-uppercase text-muted">{{ $s->eyebrow }}</small></td>
                        <td class="text-center">@if($s->is_active)<span class="badge badge-success">Aktif</span>@else<span class="badge badge-secondary">Off</span>@endif</td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.services.edit', $s) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            @include('admin.partials.delete-form', ['action' => route('admin.services.destroy', $s)])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada layanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('admin_js')
<script>
$(function() {
    $('#services-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        "columnDefs": [{ "orderable": false, "targets": [0, -1] }],
        "order": [],
        "pageLength": 25
    });
});
</script>
@stop
