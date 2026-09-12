@extends('admin.layouts.page')

@section('page_heading', 'Course FTSA')
@section('page_subheading', 'Daftar course perusahaan: kode daftar, tanggal workshop, jumlah pendaftar')

@section('page_actions')
<a href="{{ route('admin.ftsa-courses.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Course</a>
@endsection

@section('main')
@include('admin.partials.flash')

<div class="card card-outline card-primary">
    <div class="card-body table-responsive">
        <table class="table table-hover" id="ftsa-courses-table" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Course</th>
                    <th>Perusahaan</th>
                    <th>Kode daftar</th>
                    <th>Workshop</th>
                    <th>Akses sampai</th>
                    <th class="text-center">Pendaftar</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $c)
                    <tr>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td>{{ $c->company_name }}</td>
                        <td>
                            <code>{{ $c->registration_code }}</code>
                            <div class="small text-muted mt-1">
                                <a href="{{ url('/course/ftsa/daftar/'.$c->registration_code) }}" target="_blank" rel="noopener">Link daftar</a>
                            </div>
                        </td>
                        <td>{{ $c->workshop_at?->format('d M Y') }}</td>
                        <td>{{ $c->accessEndsAt()->timezone(config('portal.display_timezone', 'Asia/Jakarta'))->format('d M Y') }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.ftsa-courses.show', $c) }}">
                                {{ $c->registrants_count }}
                                @if($c->max_registrants)
                                    <span class="text-muted">/ {{ $c->max_registrants }}</span>
                                @endif
                            </a>
                        </td>
                        <td class="text-center">
                            @if(!$c->is_active)
                                <span class="badge badge-secondary">Off</span>
                            @elseif($c->registrationWindowOpen())
                                <span class="badge badge-success">Open</span>
                            @else
                                <span class="badge badge-warning">Expired</span>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.ftsa-courses.show', $c) }}" class="btn btn-sm btn-outline-info" title="Pendaftar"><i class="fas fa-users"></i></a>
                            <a href="{{ route('admin.ftsa-courses.edit', $c) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            @include('admin.partials.delete-form', ['action' => route('admin.ftsa-courses.destroy', $c)])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada course. Tambahkan dari tombol di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('admin_js')
<script>
$(function () {
    $('#ftsa-courses-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        columnDefs: [{ orderable: false, targets: [-1] }],
        order: [[3, 'desc']],
        pageLength: 25
    });
});
</script>
@stop
