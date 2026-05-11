@php
    $formId = 'delForm-' . uniqid();
    $msg    = $confirm ?? 'Item ini akan dihapus permanen.';
@endphp

<form id="{{ $formId }}" action="{{ $action }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="button" class="btn btn-sm btn-outline-danger js-swal-delete" data-form="{{ $formId }}"
            data-message="{{ $msg }}">
        <i class="fas fa-trash-alt"></i> Hapus
    </button>
</form>
