@extends('adminlte::page')

@section('title', 'Tambah Affiliate')

@section('content_header')
    <h1>Tambah Affiliate dari User Existing</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><strong>Data user yang sudah ada</strong></div>
            <form method="POST" action="{{ route('admin.affiliates.store') }}">
                @csrf
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($candidates->isNotEmpty())
                        <div class="form-group">
                            <label>Pilih dari order lunas (belum punya affiliate)</label>
                            <select id="candidate_select" class="form-control">
                                <option value="">— ketik email manual di bawah —</option>
                                @foreach($candidates as $c)
                                    <option
                                        value="{{ $c->email }}"
                                        data-name="{{ $c->full_name }}"
                                    >
                                        {{ $c->full_name }} · {{ $c->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group">
                        <label>Email user <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="affiliate_email" value="{{ old('email') }}"
                               class="form-control" required maxlength="190"
                               placeholder="harus sudah ada di order">
                        <small class="text-muted">Email harus terdaftar di order (lunas/pending).</small>
                    </div>

                    <div class="form-group">
                        <label>Nama (opsional)</label>
                        <input type="text" name="name" id="affiliate_name" value="{{ old('name') }}"
                               class="form-control" maxlength="120"
                               placeholder="Kosongkan = pakai nama dari order">
                    </div>

                    <div class="form-group">
                        <label>Kode affiliate (opsional)</label>
                        <select id="referral_code_picker" class="form-control" style="width:100%;">
                            @if(old('referral_code'))
                                <option value="{{ old('referral_code') }}" selected>{{ old('referral_code') }}</option>
                            @endif
                        </select>
                        <input type="hidden" name="referral_code" id="referral_code" value="{{ old('referral_code') }}">
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSuggestAffiliateFromName">
                                <i class="fas fa-magic mr-1"></i>Isi dari nama
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Cari berdasarkan <strong>nama</strong> / email / kode. Bisa ketik kode baru. Kosongkan = otomatis.
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-handshake mr-1"></i> Simpan Affiliate
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-outline card-info">
            <div class="card-body small">
                <p class="mb-2"><strong>Cara pakai</strong></p>
                <ul class="pl-3 mb-0">
                    <li>Pilih user dari daftar, atau ketik email order yang sudah ada.</li>
                    <li>Cari kode lewat <strong>nama</strong>, atau isi dari nama, atau kosongkan agar otomatis.</li>
                    <li>Bisa juga atur kode langsung dari <strong>Detail Order</strong>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
(function () {
    var select = document.getElementById('candidate_select');
    if (select) {
        select.addEventListener('change', function () {
            var opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) return;
            document.getElementById('affiliate_email').value = opt.value;
            document.getElementById('affiliate_name').value = opt.getAttribute('data-name') || '';
        });
    }
})();

$(function () {
    var $picker = $('#referral_code_picker');
    var $hidden = $('#referral_code');
    var searchUrl = @json(route('admin.affiliates.search'));
    var suggestUrl = @json(route('admin.affiliates.suggest-code'));

    function syncHidden(val) {
        $hidden.val(val ? String(val).toUpperCase() : '');
    }

    if ($picker.length && $.fn.select2) {
        $picker.select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: 'Cari nama / email / kode…',
            tags: true,
            ajax: {
                url: searchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                }
            },
            createTag: function (params) {
                var term = $.trim(params.term || '').toUpperCase();
                if (!term) return null;
                return { id: term, text: term + ' (kode baru)', referral_code: term };
            }
        });

        $picker.on('change', function () {
            var data = $picker.select2('data')[0];
            var code = data && (data.referral_code || data.id) ? (data.referral_code || data.id) : '';
            syncHidden(code);
            if (data && data.email && !$('#affiliate_email').val()) {
                $('#affiliate_email').val(data.email);
            }
            if (data && data.name && !$('#affiliate_name').val()) {
                $('#affiliate_name').val(data.name);
            }
        });
    }

    $('#btnSuggestAffiliateFromName').on('click', function () {
        var name = $.trim($('#affiliate_name').val() || $('input[name="name"]').val() || '');
        if (!name) {
            alert('Isi nama dulu, atau pilih user dari daftar.');
            return;
        }
        $.getJSON(suggestUrl, { name: name }).done(function (res) {
            if (!res || !res.code) return;
            var code = String(res.code).toUpperCase();
            var option = new Option(code + ' (dari nama)', code, true, true);
            $picker.append(option).trigger('change');
            syncHidden(code);
        });
    });
});
</script>
@stop
