@extends('admin.layouts.page')

@section('page_heading', 'Site Settings')
@section('page_subheading', 'Atur konten global yang muncul di seluruh halaman website')

@section('main')

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="_group" value="{{ $activeGroup }}">

    <div class="row">
        <div class="col-lg-3 mb-3">
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title mb-0">Kategori</h3></div>
                <div class="list-group list-group-flush">
                    @foreach($groups as $key => $g)
                        <a href="{{ route('admin.settings.index', ['group' => $key]) }}"
                           class="list-group-item list-group-item-action {{ $activeGroup === $key ? 'active' : '' }}">
                            <i class="{{ $g['icon'] }} mr-2"></i> {{ $g['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            @if($activeGroup === 'reviews')
                @include('admin.settings._google_business_panel')
            @endif

            <div class="card card-outline card-success">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="{{ $groups[$activeGroup]['icon'] }} mr-2"></i>{{ $groups[$activeGroup]['label'] }}
                        </h3>
                        <small class="text-muted">{{ $settings->count() }} field</small>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                </div>
                <div class="card-body">
                    @forelse($settings as $s)
                        <div class="form-row mb-4 pb-4 border-bottom">
                            <div class="col-md-4">
                                <label class="font-weight-bold text-secondary">{{ $s->label ?? $s->key }}</label>
                                <small class="text-muted d-block font-monospace">{{ $s->key }}</small>
                            </div>
                            <div class="col-md-8">
                                @if($s->type === 'textarea')
                                    <textarea name="settings[{{ $s->key }}]" rows="4" class="form-control form-control-sm">{{ $s->value }}</textarea>
                                @elseif($s->type === 'image')
                                    <input type="text" name="settings[{{ $s->key }}]" value="{{ $s->value }}"
                                           class="form-control form-control-sm font-monospace mb-2"
                                           placeholder="images/yfd-logo.png atau storage/...">
                                    @if($s->value)
                                        <div class="mb-2"><img src="{{ asset($s->value) }}" alt="" class="img-thumbnail" style="max-height:64px;"></div>
                                    @endif
                                    @if($s->key === 'brand.logo')
                                        <div class="custom-file">
                                            <input type="file" name="logo_file" accept="image/*" class="custom-file-input" id="logoUpload">
                                            <label class="custom-file-label" for="logoUpload">Upload logo header…</label>
                                        </div>
                                    @endif
                                    @if($s->key === 'brand.logo_footer')
                                        <div class="custom-file">
                                            <input type="file" name="logo_footer_file" accept="image/*" class="custom-file-input" id="logoFooterUpload">
                                            <label class="custom-file-label" for="logoFooterUpload">Upload logo footer…</label>
                                        </div>
                                        <small class="form-text text-muted">Kosongkan path di atas jika footer harus memakai logo header.</small>
                                    @endif
                                @elseif($s->type === 'number')
                                    <input type="number" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="form-control form-control-sm">
                                @else
                                    <input type="text" name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="form-control form-control-sm">
                                    @if($s->key === 'telegram.bot_url')
                                        <small class="form-text text-muted">
                                            Dipakai di WA/email pembeli & halaman sukses checkout. Contoh: <code>https://t.me/NamaBot</code> atau <code>@NamaBot</code>.
                                            Bisa juga lewat env <code>TELEGRAM_BOT_USERNAME</code>.
                                        </small>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Tidak ada setting di kategori ini.</p>
                    @endforelse

                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
