@extends('admin.layouts.page')

@section('page_heading', 'Pendaftar: '.$course->name)
@section('page_subheading', $course->company_name.' · kode '.$course->registration_code)

@section('page_actions')
<a href="{{ route('admin.ftsa-courses.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
<a href="{{ route('admin.ftsa-courses.edit', $course) }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit mr-1"></i> Edit Course</a>
@endsection

@section('main')
<div class="mb-3">
    <span class="badge badge-light border">Workshop: {{ $course->workshop_at?->format('d M Y') }}</span>
    <span class="badge badge-light border">Akses sampai: {{ $course->accessEndsAt()->timezone(config('portal.display_timezone', 'Asia/Jakarta'))->format('d M Y H:i') }}</span>
    <span class="badge badge-light border">Pendaftar: {{ $course->registrants->count() }}@if($course->max_registrants)/{{ $course->max_registrants }}@endif</span>
</div>

<div class="card card-outline card-info">
    <div class="card-body table-responsive">
        <table class="table table-hover" id="registrants-table" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>WA</th>
                    <th>Daftar</th>
                    <th>Akses sampai</th>
                    <th>Status akses</th>
                    <th>Lisensi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($course->registrants as $r)
                    <tr>
                        <td>{{ $r->full_name }}</td>
                        <td>{{ $r->email }}</td>
                        <td>{{ $r->phone ?: '—' }}</td>
                        <td>{{ $r->registered_at?->timezone(config('portal.display_timezone', 'Asia/Jakarta'))->format('d M Y H:i') }}</td>
                        <td>{{ $r->access_ends_at?->timezone(config('portal.display_timezone', 'Asia/Jakarta'))->format('d M Y H:i') }}</td>
                        <td>
                            @if($r->accessIsActive())
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Terkunci</span>
                            @endif
                        </td>
                        <td><code>{{ $r->license?->license_key ?? '—' }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pendaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('admin_js')
<script>
$(function () {
    $('#registrants-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        order: [[3, 'desc']],
        pageLength: 25
    });
});
</script>
@stop
