{{-- Wrapper AdminLTE: extend ini dari semua halaman admin --}}
@extends('adminlte::page')

@section('title')
    @yield('page_heading')
@endsection

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-0">
        <div>
            <h1 class="m-0 text-dark">@yield('page_heading')</h1>
            @hasSection('page_subheading')
                <small class="text-muted">@yield('page_subheading')</small>
            @endif
        </div>
        <div class="mt-2 mt-md-0">
            @yield('page_actions')
        </div>
    </div>
@stop

@section('content')
    @include('admin.partials.flash')
    @yield('main')
@stop

{{--
    Global JS untuk semua halaman admin. Halaman turunan boleh isi
    @section('admin_js') ... @stop untuk JS spesifik (mis. DataTables).
--}}
@section('js')
<script>
$(function () {
    // Auto-init Select2 untuk seluruh dropdown ber-class .select2
    if ($.fn.select2) {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
    }

    // Konfirmasi hapus pakai SweetAlert2
    $(document).on('click', '.js-swal-delete', function () {
        var $btn  = $(this);
        var fid   = $btn.data('form');
        var msg   = $btn.data('message') || 'Item ini akan dihapus permanen.';

        if (typeof Swal === 'undefined') {
            if (confirm(msg)) document.getElementById(fid).submit();
            return;
        }

        Swal.fire({
            title: 'Yakin hapus?',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor:  '#6c757d',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText:  'Batal',
            reverseButtons: true,
        }).then(function (result) {
            if (result.isConfirmed) {
                document.getElementById(fid).submit();
            }
        });
    });

    // Toast SweetAlert untuk flash message dari session (opsional via data attr)
    @if (session('success'))
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: @json(session('success')),
            showConfirmButton: false, timer: 3500, timerProgressBar: true,
        });
    @endif
});
</script>
@yield('admin_js')
@stop
