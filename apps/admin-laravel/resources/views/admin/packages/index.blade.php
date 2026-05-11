@extends('admin.layouts.page')

@section('page_heading', 'Paket Health Check Up')
@section('page_subheading', 'Kelola tier paket yang muncul di halaman Paket & Pertemuan')

@section('page_actions')
<a href="{{ route('admin.packages.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Paket</a>
@endsection

@section('main')

<div class="card card-outline card-success">
    <div class="card-body">
        <table id="packages-table" class="table table-hover table-striped" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Code</th>
                    <th>Nama</th>
                    <th class="text-right">Harga</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Sort</th>
                    <th class="text-right no-sort">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $p)
                    <tr>
                        <td><code>{{ $p->code }}</code></td>
                        <td>
                            <strong>{{ $p->name }}</strong>
                            @if($p->tier_label)<br><small class="text-muted">{{ $p->tier_label }}</small>@endif
                        </td>
                        <td class="text-right">
                            <strong>Rp {{ number_format($p->price, 0, ',', '.') }}</strong>
                            <small class="text-muted d-block">{{ $p->period }}</small>
                        </td>
                        <td class="text-center">
                            @if($p->is_recommended)<span class="badge badge-warning">★ Recommended</span>@endif
                            @if($p->is_active)<span class="badge badge-success">Aktif</span>@else<span class="badge badge-secondary">Nonaktif</span>@endif
                        </td>
                        <td class="text-center">{{ $p->sort }}</td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.packages.edit', $p) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            @include('admin.partials.delete-form', ['action' => route('admin.packages.destroy', $p)])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada paket. <a href="{{ route('admin.packages.create') }}">Buat pertama →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('admin_js')
<script>
$(function() {
    $('#packages-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        "columnDefs": [{ "orderable": false, "targets": [-1] }],
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});
</script>
@stop
