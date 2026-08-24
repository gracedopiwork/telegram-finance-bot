@extends('admin.layouts.page')

@php $isEdit = $product->exists; @endphp

@section('page_heading', $isEdit ? 'Edit Produk Digital' : 'Tambah Produk Digital')
@section('page_subheading', $isEdit ? "Mengedit: {$product->name}" : 'Lengkapi info produk + harga + mode pembelian')

@section('main')

<form action="{{ $isEdit ? route('admin.digital-products.update', $product) : route('admin.digital-products.store') }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row">
        {{-- ============== KIRI: Identitas + Harga ============== --}}
        <div class="col-lg-8">

            {{-- Identitas --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Identitas</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Code (slug)<span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code', $product->code) }}" placeholder="yfd-bot-telegram" required>
                            <small class="form-text text-muted">Unik. Untuk URL & Pivot clientReferenceId.</small>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-8">
                            <label>Nama Produk<span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $product->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tagline (1 kalimat singkat)</label>
                        <input type="text" name="tagline" class="form-control"
                               value="{{ old('tagline', $product->tagline) }}"
                               placeholder="Catat keuangan harian via chat — AI auto-parse ke dashboard web.">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="5"
                                  placeholder="Penjelasan lengkap produk untuk halaman publik">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Harga --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-tags mr-2"></i>Harga</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Harga Normal<span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="price" min="0" step="1000" required
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $product->price) }}" placeholder="299000">
                            </div>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label>Harga Diskon (opsional)</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="discount_price" min="0" step="1000"
                                       class="form-control @error('discount_price') is-invalid @enderror"
                                       value="{{ old('discount_price', $product->discount_price) }}" placeholder="199000">
                            </div>
                            <small class="form-text text-muted">Harus lebih kecil dari harga normal.</small>
                            @error('discount_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-2">
                            <label>Mata Uang</label>
                            <input type="text" name="currency" class="form-control" maxlength="3"
                                   value="{{ old('currency', $product->currency ?? 'IDR') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Periode</label>
                            <input type="text" name="period" class="form-control"
                                   value="{{ old('period', $product->period) }}" placeholder="selamanya / 12 bulan evaluasi">
                        </div>
                    </div>
                    @if($isEdit && $product->on_sale)
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-tag mr-1"></i>
                            Produk ini sedang diskon <strong>{{ $product->discount_percent }}%</strong>
                            ({{ $product->priceLabel($product->price) }} → {{ $product->priceLabel($product->discount_price) }})
                        </div>
                    @endif
                </div>
            </div>

            {{-- Video Demo --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-play-circle mr-2"></i>Video Demo</h3></div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" name="demo_video_enabled" id="demoVideoEnabled" class="custom-control-input" value="1"
                               @checked(old('demo_video_enabled', $product->demo_video_enabled))>
                        <label class="custom-control-label" for="demoVideoEnabled">Tampilkan video demo di halaman produk</label>
                    </div>
                    <div class="form-group">
                        <label>URL Video (YouTube / Vimeo)</label>
                        <input type="url" name="demo_video_url" class="form-control @error('demo_video_url') is-invalid @enderror"
                               value="{{ old('demo_video_url', $product->demo_video_url) }}"
                               placeholder="https://www.youtube.com/watch?v=... atau https://youtu.be/...">
                        <small class="form-text text-muted">Paste link YouTube atau Vimeo. Akan otomatis di-embed di halaman /produk.</small>
                        @error('demo_video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label>Deskripsi Video Demo</label>
                        <textarea name="demo_video_description" class="form-control" rows="4"
                                  placeholder="Penjelasan singkat di atas video — alur beli, aktivasi bot, catat transaksi, dan lihat dashboard.">{{ old('demo_video_description', $product->demo_video_description) }}</textarea>
                        <small class="form-text text-muted">Teks ini tampil di section video demo (bisa diedit kapan saja tanpa ubah deskripsi produk utama).</small>
                    </div>
                </div>
            </div>

            {{-- Fitur --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-list-ul mr-2"></i>Fitur</h3></div>
                <div class="card-body">
                    <label>Daftar Fitur (1 fitur per baris)</label>
                    <textarea name="features_text" class="form-control" rows="7"
                              placeholder="AI parser bahasa alami&#10;Dashboard web real-time&#10;Sistem lisensi pribadi">{{ old('features_text', is_array($product->features) ? implode("\n", $product->features) : '') }}</textarea>
                    <small class="form-text text-muted">Akan ditampilkan sebagai bullet list di halaman produk.</small>
                </div>
            </div>

            {{-- SEO --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header collapsed" data-toggle="collapse" data-target="#seoBlock" style="cursor:pointer;">
                    <h3 class="card-title mb-0"><i class="fas fa-search mr-2"></i>SEO (opsional)</h3>
                    <div class="card-tools"><button type="button" class="btn btn-tool"><i class="fas fa-chevron-down"></i></button></div>
                </div>
                <div class="card-body collapse" id="seoBlock">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" class="form-control"
                               value="{{ old('meta_title', $product->meta_title) }}">
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description', $product->meta_description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============== KANAN: Mode Pembelian + Status + Visual ============== --}}
        <div class="col-lg-4">

            {{-- Mode Pembelian --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-shopping-cart mr-2"></i>Mode Pembelian</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Cara Beli<span class="text-danger">*</span></label>
                        <select name="billing_mode" id="billingMode" class="form-control">
                            @foreach([
                                'pivot'    => 'Pivot (otomatis)',
                                'midtrans' => 'Midtrans (legacy)',
                                'wa'       => 'Arahkan ke WhatsApp',
                                'url'      => 'Link Eksternal',
                                'soon'     => 'Coming Soon — tampil di website, tidak bisa dibeli',
                            ] as $val => $label)
                                <option value="{{ $val }}" @selected(old('billing_mode', $product->billing_mode) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Pilih <strong>Coming Soon</strong> untuk menampilkan produk tanpa tombol checkout.
                        </small>
                    </div>
                    <div class="form-group">
                        <label>CTA Label</label>
                        <input type="text" name="cta_label" id="ctaLabel" class="form-control"
                               value="{{ old('cta_label', $product->cta_label ?? 'Beli Sekarang') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>CTA URL <small class="text-muted">(jika mode "Link Eksternal")</small></label>
                        <input type="url" name="cta_url" class="form-control"
                               value="{{ old('cta_url', $product->cta_url) }}" placeholder="https://...">
                    </div>
                </div>
            </div>

            {{-- Status & Posisi --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-toggle-on mr-2"></i>Status & Posisi</h3></div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-2">
                        <input type="checkbox" name="is_active" id="isActive" class="custom-control-input" value="1"
                               @checked(old('is_active', $isEdit ? $product->is_active : true))>
                        <label class="custom-control-label" for="isActive">Aktif (tampil di /produk)</label>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" name="is_featured" id="isFeatured" class="custom-control-input" value="1"
                               @checked(old('is_featured', $product->is_featured))>
                        <label class="custom-control-label" for="isFeatured">Featured (menu Layanan → Produk Digital & hero /produk)</label>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Badge</label>
                            <input type="text" name="badge" id="productBadge" class="form-control" maxlength="60"
                                   list="badgePresets"
                                   value="{{ old('badge', $product->badge) }}" placeholder="Tersedia / Coming Soon">
                            <datalist id="badgePresets">
                                <option value="Tersedia"></option>
                                <option value="Coming Soon"></option>
                                <option value="Baru"></option>
                                <option value="Promo"></option>
                            </datalist>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sort</label>
                            <input type="number" name="sort" class="form-control" min="0"
                                   value="{{ old('sort', $product->sort) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Visual --}}
            <div class="card card-outline card-success mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-image mr-2"></i>Visual</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Material Icon</label>
                        <input type="text" name="icon" class="form-control"
                               value="{{ old('icon', $product->icon) }}" placeholder="send / phone_iphone / school">
                        <small class="form-text text-muted">
                            Lihat: <a href="https://fonts.google.com/icons" target="_blank">fonts.google.com/icons</a>
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Upload Gambar (opsional)</label>
                        <div class="custom-file">
                            <input type="file" name="image" id="imageFile" class="custom-file-input" accept="image/*">
                            <label class="custom-file-label" for="imageFile">{{ basename($product->image_url ?? '') ?: 'Pilih file…' }}</label>
                        </div>
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="" class="img-thumbnail mt-2" style="max-height:120px;">
                            <input type="hidden" name="image_url" value="{{ $product->image_url }}">
                        @endif
                    </div>
                </div>
            </div>

            {{-- Aksi --}}
            <div class="card">
                <div class="card-body d-flex flex-wrap gap-2 justify-content-end">
                    <a href="{{ route('admin.digital-products.index') }}" class="btn btn-outline-secondary mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('admin_js')
<script>
$(function() {
    $('#imageFile').on('change', function () {
        var name = this.files[0] ? this.files[0].name : 'Pilih file…';
        $(this).next('.custom-file-label').text(name);
    });

    function syncComingSoonLabels() {
        if ($('#billingMode').val() !== 'soon') {
            return;
        }
        var badge = ($('#productBadge').val() || '').trim().toLowerCase();
        var cta = ($('#ctaLabel').val() || '').trim().toLowerCase();
        if (!badge || badge === 'tersedia') {
            $('#productBadge').val('Coming Soon');
        }
        if (!cta || cta === 'beli sekarang' || cta === 'beli') {
            $('#ctaLabel').val('Coming Soon');
        }
    }

    $('#billingMode').on('change', syncComingSoonLabels);
});
</script>
@stop
