@extends('admin.layouts.page')

@section('page_heading', 'Tambah User (Gratis)')
@section('page_subheading', 'Buat akses tanpa pembayaran — keterangan: dibuat admin.')

@section('page_actions')
<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left mr-1"></i> Semua Order
</a>
@endsection

@section('main')

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-user-plus mr-2"></i>Data user</h3>
            </div>
            <form method="POST" action="{{ route('admin.orders.store') }}">
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

                    <div class="form-group">
                        <label>Nama lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control" required maxlength="120">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required maxlength="190">
                            <small class="text-muted">Dipakai login portal + pengiriman lisensi.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label>WhatsApp</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="32" placeholder="08…">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Username Telegram</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">@</span></div>
                            <input type="text" name="telegram_username" value="{{ old('telegram_username') }}" class="form-control" maxlength="120" placeholder="opsional">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Produk akses <span class="text-danger">*</span></label>
                        <select name="digital_product_id" class="form-control" required>
                            <option value="">— pilih produk —</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" @selected(old('digital_product_id') == $p->id)>
                                    {{ $p->name }} ({{ $p->code }})
                                    @if(!$p->is_active) — nonaktif @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih produk bot / FTSA sesuai akses yang ingin diberikan.</small>
                    </div>

                    <div class="form-group">
                        <label>Kode affiliate (opsional)</label>
                        <div class="input-group">
                            <select id="referral_code_picker" class="form-control" style="width:100%;">
                                @if(old('referral_code'))
                                    <option value="{{ old('referral_code') }}" selected>{{ old('referral_code') }}</option>
                                @endif
                            </select>
                        </div>
                        <input type="hidden" name="referral_code" id="referral_code" value="{{ old('referral_code') }}">
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSuggestAffiliateFromName">
                                <i class="fas fa-magic mr-1"></i>Isi dari nama lengkap
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Cari berdasarkan <strong>nama</strong> / email / kode. Bisa juga ketik kode baru. Kosongkan = otomatis.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="admin_note" class="form-control" rows="3" maxlength="2000">{{ old('admin_note', $defaultNote) }}</textarea>
                        <small class="text-muted">Default: dibuat admin — bukan bayar. Jumlah order = Rp 0.</small>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="send_delivery" name="send_delivery" value="1" @checked(old('send_delivery'))>
                        <label class="custom-control-label" for="send_delivery">
                            Kirim email/WA ringkasan lisensi sekarang (opsional)
                        </label>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i> Buat akses gratis
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title mb-0">Catatan</h3></div>
            <div class="card-body small">
                <ul class="pl-3 mb-0">
                        <li>Order dibuat status <strong>Lunas</strong>, gateway <code>admin</code>, nominal <strong>Rp 0</strong>.</li>
                        <li>Lisensi digenerate otomatis (atau digabung jika email sudah punya lisensi aktif).</li>
                        <li>Kode affiliate bisa <strong>dicari dari nama</strong>, diisi dari nama lengkap, diketik manual, atau dikosongkan (otomatis).</li>
                        <li>Tidak ada komisi affiliate dari order gratis ini.</li>
                        <li>User bot tetap perlu <code>/activate KODE</code> di Telegram untuk menghubungkan akun.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@section('admin_js')
<script>
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
        });

        if ($hidden.val()) {
            syncHidden($hidden.val());
        }
    }

    $('#btnSuggestAffiliateFromName').on('click', function () {
        var name = $.trim($('input[name="full_name"]').val() || '');
        if (!name) {
            alert('Isi nama lengkap dulu.');
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
