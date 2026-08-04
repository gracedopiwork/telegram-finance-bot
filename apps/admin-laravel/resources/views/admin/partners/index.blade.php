@extends('admin.layouts.page')

@section('page_heading', 'Partnership')
@section('page_subheading', 'Mitra di section Partner for Financial Support (halaman Layanan & Penasihat)')

@section('page_actions')
<a href="{{ route('admin.partners.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Partner</a>
@endsection

@section('main')

<div class="card card-outline card-success">
    <div class="card-body">
        <table id="partners-table" class="table table-hover" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Nama</th>
                    <th>Icon</th>
                    <th class="text-center">Urutan</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partners as $p)
                    <tr>
                        <td>
                            <strong>{{ $p->title }}</strong>
                            @if($p->description)
                                <br><small class="text-muted">{{ Str::limit($p->description, 90) }}</small>
                            @endif
                        </td>
                        <td><code>{{ $p->icon }}</code></td>
                        <td class="text-center">{{ $p->sort }}</td>
                        <td class="text-center">@if($p->is_active)<span class="badge badge-success">Aktif</span>@else<span class="badge badge-secondary">Off</span>@endif</td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.partners.edit', $p) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            @include('admin.partials.delete-form', ['action' => route('admin.partners.destroy', $p)])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada partner. Tambahkan dari tombol di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('admin_js')
<script>
$(function() {
    $('#partners-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        "columnDefs": [{ "orderable": false, "targets": [-1] }],
        "order": [[2, "asc"]],
        "pageLength": 25
    });
});
</script>
@stop
