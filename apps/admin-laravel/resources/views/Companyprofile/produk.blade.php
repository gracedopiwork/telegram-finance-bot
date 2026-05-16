@extends('Companyprofile.layouts.main')

@section('title', $featured?->meta_title ?? 'Produk Digital — YFD')
@section('description', $featured?->meta_description ?? 'Ekosistem produk digital YFD. Mulai dari YFD Bot Telegram untuk catat keuangan harian via chat dengan AI parsing otomatis.')

@section('content')

{{-- ============== Hero ============== --}}
<section class="relative isolate overflow-hidden bg-primary text-on-primary">
    <div class="absolute inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary/95 to-primary-container/80"></div>
        <div class="absolute inset-0 bg-hero-radial"></div>
        <div class="absolute inset-0 bg-hero-grid bg-[size:36px_36px] opacity-30"></div>
    </div>
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-16 md:py-24">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 bg-secondary-container/95 text-on-secondary-container px-4 py-1.5 rounded-full text-label-md font-semibold">
                <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                PRODUK DIGITAL YFD
            </span>
            <h1 class="font-display text-[36px] sm:text-[44px] md:text-display-lg font-extrabold leading-[1.1] mt-5">
                Tools digital untuk <span class="text-secondary-fixed">membentuk kebiasaan finansial sehat</span>.
            </h1>
            <p class="text-body-lg text-white/85 mt-5 max-w-2xl">
                Bukan agen produk asuransi/investasi. Yang kami tawarkan adalah aplikasi & tools digital yang
                membantu Anda mencatat, memahami, dan mengevaluasi kondisi keuangan harian secara otomatis.
            </p>
        </div>
    </div>
</section>

{{-- ============== FLAGSHIP PRODUCT (dari DB) ============== --}}
@if($featured)
<section class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop -mt-10 md:-mt-14 relative z-10">
    <div class="bg-white rounded-3xl border border-outline-variant shadow-lift overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-0">

            {{-- Left: copy + price --}}
            <div class="lg:col-span-7 p-8 md:p-12">
                <div class="flex items-center gap-2 mb-5 flex-wrap">
                    <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-[11px] uppercase tracking-widest font-bold">
                        ★ Flagship
                    </span>
                    @if($featured->badge)
                        <span class="px-3 py-1 rounded-full text-[11px] uppercase tracking-widest font-bold
                                     {{ str_contains(strtolower($featured->badge), 'soon') ? 'bg-surface-container-high text-on-surface-variant' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $featured->badge }}
                        </span>
                    @endif
                    @if(str_contains(strtolower($featured->name ?? ''), 'telegram'))
                        <span class="bg-[#0088CC]/10 text-[#0088CC] px-3 py-1 rounded-full text-[11px] uppercase tracking-widest font-bold flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px]">send</span> Telegram
                        </span>
                    @endif
                </div>

                <h2 class="font-display text-[28px] md:text-[40px] font-extrabold text-primary leading-[1.1] mb-3">
                    {{ $featured->name }}
                </h2>
                @if($featured->tagline)
                    <p class="text-headline-md font-semibold text-primary-container mb-3">
                        {{ $featured->tagline }}
                    </p>
                @endif
                @if($featured->description)
                    <p class="text-body-md text-on-surface-variant mb-6 max-w-xl">{{ $featured->description }}</p>
                @endif

                {{-- Price block --}}
                @if($featured->price > 0 && $featured->billing_mode !== 'soon')
                    <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-5 mb-6 inline-flex flex-wrap items-end gap-x-5 gap-y-2">
                        @if($featured->on_sale)
                            <div>
                                <div class="text-[12px] uppercase tracking-widest text-on-surface-variant font-semibold mb-1">Harga Promo</div>
                                <div class="flex items-baseline gap-3">
                                    <span class="font-display text-[36px] md:text-[44px] font-extrabold text-primary-container leading-none">{{ $featured->priceLabel($featured->discount_price) }}</span>
                                    <span class="text-[18px] text-on-surface-variant line-through">{{ $featured->priceLabel($featured->price) }}</span>
                                    <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[11px] font-bold">−{{ $featured->discount_percent }}%</span>
                                </div>
                                <div class="text-[12px] text-on-surface-variant mt-1">{{ $featured->period }}</div>
                            </div>
                        @else
                            <div>
                                <div class="text-[12px] uppercase tracking-widest text-on-surface-variant font-semibold mb-1">Harga</div>
                                <div class="font-display text-[36px] md:text-[44px] font-extrabold text-primary-container leading-none">{{ $featured->priceLabel($featured->price) }}</div>
                                <div class="text-[12px] text-on-surface-variant mt-1">{{ $featured->period }}</div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Features --}}
                @if(is_array($featured->features) && count($featured->features))
                    <ul class="space-y-3 mb-7">
                        @foreach($featured->features as $f)
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-emerald-600 mt-0.5" style="font-variation-settings:'FILL' 1;">check_circle</span>
                                <span class="text-body-md text-on-surface">{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- CTA buttons --}}
                <div class="flex flex-wrap gap-3">
                    @if($featured->billing_mode === 'midtrans')
                        <a href="{{ route('checkout.show', $featured->code) }}" class="btn btn-primary btn-lg">
                            <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                            {{ $featured->cta_label ?? 'Beli Sekarang' }}
                        </a>
                    @elseif($featured->billing_mode === 'wa')
                        <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                            <span class="material-symbols-outlined text-[20px]">chat</span>
                            {{ $featured->cta_label ?? 'Pesan via WhatsApp' }}
                        </a>
                    @elseif($featured->billing_mode === 'url' && $featured->cta_url)
                        <a href="{{ $featured->cta_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                            <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                            {{ $featured->cta_label ?? 'Akses Produk' }}
                        </a>
                    @else
                        <button type="button" disabled class="btn btn-primary btn-lg opacity-60 cursor-not-allowed">
                            <span class="material-symbols-outlined text-[20px]">schedule</span>
                            Coming Soon
                        </button>
                    @endif
                    <a href="#cara-pakai" class="btn btn-outline-primary btn-lg">
                        <span class="material-symbols-outlined text-[20px]">play_arrow</span>
                        Lihat Cara Pakai
                    </a>
                </div>
            </div>

            {{-- Right: chat mockup --}}
            <div class="lg:col-span-5 bg-gradient-to-br from-primary-container to-primary p-6 md:p-10 flex items-center">
                <div class="w-full max-w-sm mx-auto bg-[#0e1c33] rounded-2xl shadow-2xl overflow-hidden border border-white/10">
                    <div class="bg-[#142841] px-4 py-3 flex items-center gap-3 border-b border-white/5">
                        <div class="w-9 h-9 rounded-full bg-secondary-container grid place-items-center text-on-secondary-container font-bold text-[14px]">YFD</div>
                        <div>
                            <div class="text-white font-semibold text-[14px] leading-tight">YFD Finance Bot</div>
                            <div class="text-emerald-400 text-[11px] leading-tight">● online · Indonesia</div>
                        </div>
                    </div>
                    <div class="px-4 py-5 space-y-3 bg-[#0a1626]">
                        <div class="flex justify-end">
                            <div class="bg-[#2b5278] text-white text-[13.5px] px-3 py-2 rounded-2xl rounded-br-sm max-w-[80%] shadow">
                                makan malam 50rb di warung deket kampus
                                <div class="text-[10px] text-white/50 text-right mt-1">19:24 ✓✓</div>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="bg-[#1a2c45] text-white/90 text-[13px] px-3 py-2 rounded-2xl rounded-bl-sm max-w-[88%] shadow">
                                <div class="text-emerald-400 text-[11px] font-bold mb-1">✓ Tercatat</div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-[12px]">
                                    <span class="text-white/60">Nominal</span><span class="text-amber-300 font-semibold">Rp 50.000</span>
                                    <span class="text-white/60">Kategori</span><span>Makan</span>
                                    <span class="text-white/60">Sifat</span><span>Need</span>
                                    <span class="text-white/60">Mood</span><span>Biasa Saja</span>
                                    <span class="text-white/60">Impulsif</span><span>No</span>
                                </div>
                                <div class="text-[10px] text-white/40 text-right mt-1.5">19:24</div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="bg-[#2b5278] text-white text-[13.5px] px-3 py-2 rounded-2xl rounded-br-sm max-w-[80%] shadow">
                                beli kopi 18000 karena ngantuk
                                <div class="text-[10px] text-white/50 text-right mt-1">19:25 ✓✓</div>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="bg-[#1a2c45] text-white/90 text-[13px] px-3 py-2 rounded-2xl rounded-bl-sm max-w-[88%] shadow">
                                <div class="text-emerald-400 text-[11px] font-bold mb-1">✓ Tercatat</div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-[12px]">
                                    <span class="text-white/60">Nominal</span><span class="text-amber-300 font-semibold">Rp 18.000</span>
                                    <span class="text-white/60">Sifat</span><span class="text-pink-300">Wants</span>
                                    <span class="text-white/60">Impulsif</span><span class="text-pink-300 font-bold">Yes ⚡</span>
                                </div>
                                <div class="text-[10px] text-white/40 text-right mt-1.5">19:25</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-[#142841] px-3 py-2.5 flex items-center gap-2 border-t border-white/5">
                        <span class="material-symbols-outlined text-white/40 text-[20px]">attach_file</span>
                        <div class="flex-1 bg-[#0a1626] rounded-full px-3 py-1.5 text-white/40 text-[12px]">Ketik transaksi…</div>
                        <span class="material-symbols-outlined text-[#3b82f6] text-[22px]">send</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============== Cara Pakai (3 langkah) ============== --}}
<section id="cara-pakai" class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-20">
    <div class="text-center mb-12 max-w-2xl mx-auto">
        <span class="text-label-md text-secondary block mb-3">CARA PAKAI</span>
        <h2 class="font-heading text-headline-lg text-primary mb-3">3 langkah, 5 menit, langsung dipakai.</h2>
        <p class="text-body-md text-on-surface-variant">Tidak perlu install aplikasi tambahan. Cukup punya Telegram & Gmail.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        @foreach([
            ['no' => '01', 'ic' => 'shopping_cart',    'title' => 'Beli & Bayar Online',     'desc' => 'Klik tombol Beli Sekarang, isi form, lanjutkan ke Midtrans (kartu/VA/e-wallet/QRIS).'],
            ['no' => '02', 'ic' => 'verified_user',    'title' => 'Aktivasi di Bot',         'desc' => 'Salin kode dari halaman setelah bayar (sama dengan email). Di YFD Bot: /activate KODE-LISENSI (harus persis).'],
            ['no' => '03', 'ic' => 'forum',            'title' => 'Catat Sambil Ngobrol',    'desc' => 'Tinggal chat: "bensin 50rb", "gajian 5jt", "nabung 200rb". Bot urus sisanya.'],
        ] as $step)
            <div class="bg-white border border-outline-variant rounded-2xl p-7 shadow-soft hover:shadow-card transition relative overflow-hidden">
                <span class="absolute -top-4 -right-2 text-[120px] font-extrabold text-surface-container-high leading-none select-none pointer-events-none">{{ $step['no'] }}</span>
                <div class="relative">
                    <span class="w-12 h-12 rounded-xl bg-primary-container/10 grid place-items-center mb-4">
                        <span class="material-symbols-outlined text-primary-container">{{ $step['ic'] }}</span>
                    </span>
                    <h3 class="font-heading text-[18px] font-bold text-primary mb-2">{{ $step['title'] }}</h3>
                    <p class="text-body-md text-on-surface-variant">{{ $step['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ============== Apa yang dicatat ============== --}}
<section class="bg-surface-container-low border-y border-outline-variant py-20">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="text-center mb-10 max-w-2xl mx-auto">
            <span class="text-label-md text-secondary block mb-3">APA YANG DICATAT</span>
            <h2 class="font-heading text-headline-lg text-primary mb-3">Bot otomatis klasifikasikan 7 dimensi finansial</h2>
            <p class="text-body-md text-on-surface-variant">Sesuai framework <em>Financial Health Check Up</em> YFD — siap analisis lanjut bersama dokter finansial.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            @foreach([
                ['ic' => 'description',         'label' => 'Keterangan'],
                ['ic' => 'payments',            'label' => 'Nominal'],
                ['ic' => 'swap_horiz',          'label' => 'Jenis'],
                ['ic' => 'category',            'label' => 'Kategori'],
                ['ic' => 'fact_check',          'label' => 'Sifat'],
                ['ic' => 'sentiment_satisfied', 'label' => 'Mood'],
                ['ic' => 'flash_on',            'label' => 'Impulsif'],
            ] as $d)
                <div class="bg-white border border-outline-variant rounded-xl p-4 text-center hover:border-primary-container/50 transition">
                    <span class="material-symbols-outlined text-primary-container text-[24px]">{{ $d['ic'] }}</span>
                    <div class="text-[12px] font-semibold text-primary mt-1">{{ $d['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============== Produk Lain (Other digital products) ============== --}}
@if($others->count())
<section class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-20">
    <div class="text-center mb-12 max-w-2xl mx-auto">
        <span class="text-label-md text-secondary block mb-3">PRODUK LAIN</span>
        <h2 class="font-heading text-headline-lg text-primary mb-3">Roadmap & produk pendukung</h2>
        <p class="text-body-md text-on-surface-variant">Sedang dalam pengembangan atau sudah siap dipakai.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($others as $p)
            <div class="bg-white border {{ $p->billing_mode === 'soon' ? 'border-dashed border-outline' : 'border-outline-variant' }} rounded-2xl p-7 shadow-soft hover:shadow-card transition flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <span class="w-12 h-12 rounded-xl bg-primary-container/10 grid place-items-center">
                        <span class="material-symbols-outlined text-primary-container">{{ $p->icon }}</span>
                    </span>
                    @if($p->badge)
                        <span class="text-[10px] uppercase tracking-widest font-bold px-2 py-1 rounded-full
                                     {{ str_contains(strtolower($p->badge), 'soon') ? 'bg-surface-container-high text-on-surface-variant' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $p->badge }}
                        </span>
                    @endif
                </div>
                <h3 class="font-heading text-[18px] font-bold text-primary mb-2">{{ $p->name }}</h3>
                @if($p->tagline)
                    <p class="text-body-md text-on-surface-variant mb-4 flex-grow">{{ $p->tagline }}</p>
                @endif

                @if($p->price > 0 && $p->billing_mode !== 'soon')
                    <div class="mb-4">
                        @if($p->on_sale)
                            <div class="flex items-baseline gap-2">
                                <span class="font-display text-[22px] font-extrabold text-primary-container">{{ $p->priceLabel($p->discount_price) }}</span>
                                <span class="text-[13px] text-on-surface-variant line-through">{{ $p->priceLabel($p->price) }}</span>
                                <span class="bg-red-100 text-red-700 px-1.5 py-0.5 rounded text-[10px] font-bold">−{{ $p->discount_percent }}%</span>
                            </div>
                        @else
                            <span class="font-display text-[22px] font-extrabold text-primary-container">{{ $p->priceLabel($p->price) }}</span>
                        @endif
                        <div class="text-[12px] text-on-surface-variant">{{ $p->period }}</div>
                    </div>
                @endif

                @if($p->billing_mode === 'midtrans')
                    <a href="{{ route('checkout.show', $p->code) }}" class="btn btn-primary btn-sm justify-center mt-auto">
                        <span class="material-symbols-outlined text-[18px]">shopping_cart</span> {{ $p->cta_label ?? 'Beli' }}
                    </a>
                @elseif($p->billing_mode === 'wa')
                    <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm justify-center mt-auto">
                        <span class="material-symbols-outlined text-[18px]">chat</span> {{ $p->cta_label ?? 'Pesan via WA' }}
                    </a>
                @elseif($p->billing_mode === 'url' && $p->cta_url)
                    <a href="{{ $p->cta_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm justify-center mt-auto">
                        <span class="material-symbols-outlined text-[18px]">open_in_new</span> {{ $p->cta_label ?? 'Buka' }}
                    </a>
                @else
                    <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm justify-center mt-auto">
                        <span class="material-symbols-outlined text-[18px]">notifications_active</span> {{ $p->cta_label ?? 'Notify Saat Launch' }}
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- ============== Banner cross-sell ============== --}}
<section class="bg-primary text-on-primary py-16 relative overflow-hidden">
    <div class="absolute inset-0 bg-hero-radial pointer-events-none"></div>
    <div class="relative max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="grid md:grid-cols-2 gap-10 items-center">
            <div>
                <span class="text-label-md text-secondary-fixed block mb-3">EKOSISTEM YFD</span>
                <h2 class="font-heading text-headline-lg mb-4">Tools digital saja tidak cukup tanpa pendampingan.</h2>
                <p class="text-body-md text-white/85 max-w-md">
                    Bot YFD memberi Anda data harian. Tapi membaca data, mengevaluasi, dan
                    merancang strategi adalah pekerjaan dokter finansial. Gabungkan keduanya.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ $primaryCheckupUrl }}" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif class="bg-white/10 hover:bg-white/15 border border-white/15 rounded-xl p-5 transition">
                    <span class="material-symbols-outlined text-secondary-fixed text-[28px] mb-2 block">monitor_heart</span>
                    <div class="font-bold text-[15px]">Health Check Up</div>
                    <div class="text-[12.5px] text-white/70 mt-1">Diagnosa kondisi finansial</div>
                </a>
                <a href="{{ route('company.pertemuan') }}" class="bg-white/10 hover:bg-white/15 border border-white/15 rounded-xl p-5 transition">
                    <span class="material-symbols-outlined text-secondary-fixed text-[28px] mb-2 block">forum</span>
                    <div class="font-bold text-[15px]">Konsultasi 1-on-1</div>
                    <div class="text-[12.5px] text-white/70 mt-1">Sesi dengan dokter QWP</div>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ============== Disclaimer ============== --}}
<section class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12">
    <div class="bg-secondary-container/30 border border-secondary-container/50 rounded-2xl p-7 md:p-9 flex items-start gap-5">
        <span class="material-symbols-outlined text-primary-container text-[36px] flex-shrink-0">verified</span>
        <div>
            <h3 class="font-heading text-[18px] font-bold text-primary mb-2">YFD Bukan Agen Produk Keuangan</h3>
            <p class="text-body-md text-on-surface-variant">
                Kami tidak menjual asuransi, reksadana, atau saham. Tools digital kami berfokus pada
                <strong>edukasi & pendampingan</strong> — Anda tetap memegang kendali penuh atas keputusan finansial.
            </p>
        </div>
    </div>
</section>

@endsection
