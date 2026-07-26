@extends('Companyprofile.layouts.main')

@section('title', $featured?->meta_title ?? 'Produk Digital — YFD')
@section('description', $featured?->meta_description ?? 'Ekosistem produk digital YFD. Mulai dari YFD First Aid untuk catat keuangan harian via chat dengan AI parsing otomatis.')

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
                @php($descriptionText = trim((string) ($featured->description ?? '')))
                @if($descriptionText !== '')
                    <div class="text-body-md text-on-surface-variant mb-6 max-w-xl space-y-3">
                        @foreach(preg_split('/\n\s*\n/', $descriptionText) ?: [] as $paragraph)
                            @if(trim($paragraph) !== '')
                                <p>{{ trim($paragraph) }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($featured->price > 0 && $featured->billing_mode !== 'soon' && $featured->period)
                    <p class="text-[13px] text-primary-container/90 mb-6 max-w-xl">
                        @if($featured->code === 'yfd-ftsa-premium')
                            <span class="material-symbols-outlined text-[16px] align-[-3px]">schedule</span>
                            Masa aktif: <strong>{{ $featured->period }}</strong> sejak pembayaran lunas.
                        @elseif($featured->code === 'yfd-bot-telegram')
                            <span class="material-symbols-outlined text-[16px] align-[-3px]">schedule</span>
                            Termasuk <strong>biaya admin 1 tahun</strong>. Tahun berikutnya: Rp10.000/bulan atau Rp99.000/tahun.
                        @elseif(in_array($featured->code, ['yfd-bot-admin-monthly', 'yfd-bot-admin-yearly'], true))
                            <span class="material-symbols-outlined text-[16px] align-[-3px]">autorenew</span>
                            Perpanjang akses bot &amp; dashboard: <strong>{{ $featured->period }}</strong>.
                        @endif
                    </p>
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
                                <div class="text-[12px] text-on-surface-variant mt-1 font-semibold">{{ $featured->period }}</div>
                            </div>
                        @else
                            <div>
                                <div class="text-[12px] uppercase tracking-widest text-on-surface-variant font-semibold mb-1">Harga</div>
                                <div class="font-display text-[36px] md:text-[44px] font-extrabold text-primary-container leading-none">{{ $featured->priceLabel($featured->price) }}</div>
                                <div class="text-[12px] text-on-surface-variant mt-1 font-semibold">{{ $featured->period }}</div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Features --}}
                @if(is_array($featured->features) && count($featured->features))
                    <ul class="space-y-3 mb-7">
                        @foreach($featured->features as $f)
                            @php($featureText = (string) $f)
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-emerald-600 mt-0.5" style="font-variation-settings:'FILL' 1;">check_circle</span>
                                <span class="text-body-md text-on-surface">{{ $featureText }}</span>
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
                    <a href="{{ route('portal.login') }}" class="btn btn-outline-primary btn-lg">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>
                        Buka Dashboard
                    </a>
                </div>
            </div>

            {{-- Right: chat mockup — YFD First Aid + AI impulsif detection --}}
            <div class="lg:col-span-5 bg-gradient-to-br from-primary-container to-primary p-6 md:p-10 flex items-center">
                <div class="w-full max-w-sm mx-auto rounded-2xl shadow-2xl overflow-hidden border border-white/10" style="background: {{ config('yfd_brand.navy') }}">
                    <div class="px-4 py-3 flex items-center gap-3 border-b border-white/10" style="background: {{ config('yfd_brand.navy_700') }}">
                        <div class="w-9 h-9 rounded-full grid place-items-center font-bold text-[14px]" style="background: {{ config('yfd_brand.mint') }}; color: {{ config('yfd_brand.navy') }}">YFD</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-white font-semibold text-[14px] leading-tight">YFD First Aid</div>
                            <div class="text-[11px] leading-tight" style="color: {{ config('yfd_brand.mint_light') }}">● AI aktif · deteksi impulsif</div>
                        </div>
                        <span class="shrink-0 text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border" style="background: {{ config('yfd_brand.gold') }}22; color: {{ config('yfd_brand.gold') }}; border-color: {{ config('yfd_brand.gold') }}55">Claude</span>
                    </div>
                    <div class="px-4 py-5 space-y-3" style="background: {{ config('yfd_brand.navy_900') }}">
                        <div class="flex justify-end">
                            <div class="text-white text-[13.5px] px-3 py-2 rounded-2xl rounded-br-sm max-w-[85%] shadow" style="background: {{ config('yfd_brand.navy_600') }}">
                                makan malam 50rb di warung deket kampus
                                <div class="text-[10px] text-white/50 text-right mt-1">19:24 ✓✓</div>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="text-white/90 text-[13px] px-3 py-2 rounded-2xl rounded-bl-sm max-w-[92%] shadow" style="background: {{ config('yfd_brand.navy_700') }}">
                                <div class="flex items-center gap-1.5 text-[11px] font-bold mb-1.5" style="color: {{ config('yfd_brand.mint_light') }}">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span> Tercatat
                                </div>
                                <div class="rounded-lg px-2 py-1.5 mb-2 text-[10.5px] leading-snug border" style="background: {{ config('yfd_brand.mint') }}18; border-color: {{ config('yfd_brand.mint') }}44; color: {{ config('yfd_brand.mint_light') }}">
                                    <span class="font-bold" style="color: {{ config('yfd_brand.mint') }}">AI:</span> Kebutuhan terencana — tidak terdeteksi pola impulsif.
                                </div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-[12px]">
                                    <span class="text-white/60">Nominal</span><span class="font-semibold" style="color: {{ config('yfd_brand.gold') }}">Rp 50.000</span>
                                    <span class="text-white/60">Kategori</span><span>Makan</span>
                                    <span class="text-white/60">Sifat</span><span>Need</span>
                                    <span class="text-white/60">Impulsif</span><span style="color: {{ config('yfd_brand.mint') }}">No</span>
                                </div>
                                <div class="text-[10px] text-white/40 text-right mt-1.5">19:24</div>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="text-white text-[13.5px] px-3 py-2 rounded-2xl rounded-br-sm max-w-[85%] shadow" style="background: {{ config('yfd_brand.navy_600') }}">
                                beli kopi starbuck 79k karena ngantuk mau kerja, abis begadang nonton piala dunia
                                <div class="text-[10px] text-white/50 text-right mt-1">19:25 ✓✓</div>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="text-white/90 text-[13px] px-3 py-2 rounded-2xl rounded-bl-sm max-w-[92%] shadow ring-1" style="background: {{ config('yfd_brand.navy_700') }}; ring-color: {{ config('yfd_brand.gold') }}44">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <div class="flex items-center gap-1.5 text-[11px] font-bold" style="color: {{ config('yfd_brand.gold') }}">
                                        <span class="material-symbols-outlined text-[14px]">psychology</span> AI mendeteksi impulsif
                                    </div>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded" style="background: {{ config('yfd_brand.gold') }}22; color: {{ config('yfd_brand.gold') }}">⚡ Yes</span>
                                </div>
                                <div class="rounded-lg border px-2.5 py-2 mb-2 space-y-1" style="background: linear-gradient(90deg, {{ config('yfd_brand.gold') }}18, {{ config('yfd_brand.mint') }}12); border-color: {{ config('yfd_brand.gold') }}44">
                                    <div class="text-[10.5px] leading-snug text-white/90">
                                        <span class="font-bold" style="color: {{ config('yfd_brand.gold') }}">Pemicu emosional:</span> kelelahan (ngantuk) + begadang nonton piala dunia
                                    </div>
                                    <div class="text-[10.5px] leading-snug text-white/85">
                                        <span class="font-bold" style="color: {{ config('yfd_brand.mint_light') }}">Pola:</span> regulator mood via belanja premium (Wants) — bukan kebutuhan kerja mendesak
                                    </div>
                                    <div class="text-[10px] text-white/55 italic border-t border-white/10 pt-1">
                                        dr. Financial: coba jeda 10 menit sebelum transaksi serupa.
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-[12px]">
                                    <span class="text-white/60">Nominal</span><span class="font-semibold" style="color: {{ config('yfd_brand.gold') }}">Rp 79.000</span>
                                    <span class="text-white/60">Kategori</span><span>Makanan & Minuman</span>
                                    <span class="text-white/60">Mood</span><span>Tired</span>
                                    <span class="text-white/60">Sifat</span><span class="font-semibold" style="color: {{ config('yfd_brand.gold') }}">Wants</span>
                                </div>
                                <div class="text-[10px] text-white/40 text-right mt-1.5">19:25</div>
                            </div>
                        </div>
                    </div>
                    <div class="px-3 py-2.5 flex items-center gap-2 border-t border-white/10" style="background: {{ config('yfd_brand.navy_700') }}">
                        <span class="material-symbols-outlined text-white/40 text-[20px]">attach_file</span>
                        <div class="flex-1 rounded-full px-3 py-1.5 text-white/40 text-[12px]" style="background: {{ config('yfd_brand.navy_900') }}">Ceritakan transaksi &amp; perasaan Anda…</div>
                        <span class="material-symbols-outlined text-[22px]" style="color: {{ config('yfd_brand.mint_light') }}">send</span>
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
        <h2 class="font-heading text-headline-lg text-primary mb-3">4 langkah dari beli sampai dashboard.</h2>
        <p class="text-body-md text-on-surface-variant">Tidak perlu install aplikasi tambahan. Cukup punya Telegram & Gmail.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach([
            ['no' => '01', 'ic' => 'shopping_cart',    'title' => 'Beli & Bayar Online',     'desc' => 'Klik Beli Sekarang, isi form, bayar via QRIS Midtrans.'],
            ['no' => '02', 'ic' => 'verified_user',    'title' => 'Aktivasi di Bot',         'desc' => 'Salin kode lisensi. Di YFD First Aid: /activate KODE-LISENSI.'],
            ['no' => '03', 'ic' => 'forum',            'title' => 'Catat via Chat',          'desc' => 'Chat: "bensin 50rb", "gajian 5jt". AI parse kategori, mood & impulsif.'],
            ['no' => '04', 'ic' => 'dashboard',        'title' => 'Lihat Dashboard',         'desc' => 'Login portal → isi baseline → lihat Financial & Behavioral Dashboard + insight.'],
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

@if($featured?->hasDemoVideo())
{{-- ============== Video Demo (dikelola admin) ============== --}}
<section id="video-demo" class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-20">
        <div class="max-w-3xl mx-auto text-center mb-8">
            <span class="text-label-md text-secondary block mb-3">VIDEO DEMO</span>
            <h2 class="font-heading text-headline-lg text-primary mb-3">Lihat alur lengkap YFD First Aid</h2>
            @if(filled($featured->demo_video_description))
                <p class="text-body-md text-on-surface-variant whitespace-pre-line">{{ $featured->demo_video_description }}</p>
            @else
                <p class="text-body-md text-on-surface-variant">Dari beli &amp; bayar, aktivasi bot, catat transaksi harian, sampai melihat dashboard finansial &amp; behavioral.</p>
            @endif
        </div>
        <div class="max-w-4xl mx-auto">
            <div class="relative w-full rounded-2xl overflow-hidden shadow-lift border border-outline-variant bg-black aspect-video">
                <iframe
                    src="{{ $featured->demo_video_embed_url }}"
                    title="Video demo {{ $featured->name }}"
                    class="absolute inset-0 w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        </div>
</section>
@endif

{{-- ============== Apa yang dicatat ============== --}}
<section class="bg-surface-container-low border-y border-outline-variant py-20">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="text-center mb-10 max-w-2xl mx-auto">
            <span class="text-label-md text-secondary block mb-3">APA YANG DICATAT</span>
            <h2 class="font-heading text-headline-lg text-primary mb-3">YFD First Aid otomatis klasifikasikan transaksi harian</h2>
            <p class="text-body-md text-on-surface-variant">Kategori, sifat (Need/Wants), mood, dan deteksi impulsif — siap dianalisis di dashboard behavioral.</p>
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
                    <p class="text-body-md font-semibold text-primary mb-3">{{ $p->tagline }}</p>
                @endif
                @php($otherDesc = trim((string) ($p->description ?? '')))
                @if($otherDesc !== '')
                    <div class="text-body-sm text-on-surface-variant mb-4 flex-grow space-y-2">
                        @foreach(preg_split('/\n\s*\n/', $otherDesc) ?: [] as $paragraph)
                            @if(trim($paragraph) !== '')
                                <p>{{ trim($paragraph) }}</p>
                            @endif
                        @endforeach
                    </div>
                @elseif($p->tagline)
                    <div class="mb-4 flex-grow"></div>
                @endif
                @if(is_array($p->features) && count($p->features))
                    <p class="text-[12px] uppercase tracking-wider font-bold text-on-surface-variant mb-2">Yang akan Anda dapatkan</p>
                    <ul class="space-y-2 mb-4">
                        @foreach($p->features as $f)
                            <li class="flex items-start gap-2 text-body-sm text-on-surface">
                                <span class="material-symbols-outlined text-emerald-600 text-[16px] mt-0.5" style="font-variation-settings:'FILL' 1;">check_circle</span>
                                <span>{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
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
                        <div class="text-[12px] text-on-surface-variant mt-1 font-semibold">{{ $p->period }}</div>
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
                @elseif($p->billing_mode === 'soon')
                    <button type="button" disabled class="btn btn-outline-primary btn-sm justify-center mt-auto opacity-60 cursor-not-allowed">
                        <span class="material-symbols-outlined text-[18px]">schedule</span> Coming Soon
                    </button>
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
                    YFD First Aid memberi Anda data harian. Tapi membaca data, mengevaluasi, dan
                    merancang strategi adalah pekerjaan dokter finansial. Gabungkan keduanya.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ $primaryCheckupUrl }}" class="bg-white/10 hover:bg-white/15 border border-white/15 rounded-xl p-5 transition">
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
