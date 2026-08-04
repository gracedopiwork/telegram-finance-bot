@extends('Companyprofile.layouts.main')

@section('title', 'Your Financial Doctor — Indonesia\'s First Financial Health Center')

@php
    $googleMapsUrl = $reviews['reviews.google_maps_url']
        ?? ($yfd['google_maps_url'] ?? null)
        ?? 'https://www.google.com/search?q=Your%20Financial%20Doctor&stick=H4sIAAAAAAAAAONgU1I1qDAyTzYzSjQzTzZMS7YwNza1MqhISbNINTQzSU1OS05NtDBMXMQqGplfWqTglpmXmJecmZij4JKfXJJfBABo0kI0QQAAAA&mat=CT8GW_tbUj0c#mpd=~2034873161653880383/customers/reviews';
    $googleRating = $reviews['reviews.google_rating'] ?? '5.0';
    $googleCount = $reviews['reviews.google_count'] ?? null;
    $synced = isset($syncedReviews) ? collect($syncedReviews) : collect();
    if ($synced->isNotEmpty()) {
        $reviewItems = $synced->map(fn ($r) => [
            'name' => $r->reviewer_name,
            'text' => $r->comment,
            'rating' => (string) $r->rating,
        ])->values();
    } else {
        $reviewItems = collect(range(1, 6))->map(function ($i) use ($reviews) {
            return [
                'name' => $reviews["reviews.r{$i}.name"] ?? null,
                'text' => $reviews["reviews.r{$i}.text"] ?? null,
                'rating' => $reviews["reviews.r{$i}.rating"] ?? '5',
            ];
        })->filter(fn ($r) => filled($r['name']) && filled($r['text']))->values();
    }
@endphp

@section('content')

{{-- ============== Hero Section ============== --}}
<section class="relative isolate overflow-hidden bg-primary text-on-primary">
    {{-- Background image with gradient overlay --}}
    <div class="absolute inset-0 -z-10">
        <img class="w-full h-full object-cover opacity-30"
             src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1920&q=80"
             alt="Modern professional financial diagnostic center.">
        <div class="absolute inset-0 bg-gradient-to-r from-primary via-primary/95 to-primary/70"></div>
        <div class="absolute inset-0 bg-hero-radial"></div>
        <div class="absolute inset-0 bg-hero-grid bg-[size:36px_36px] opacity-30"></div>
    </div>

    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop pt-20 md:pt-28 pb-32 md:pb-40">
        <div class="max-w-4xl">
            <span class="inline-flex items-center gap-2 bg-secondary-container/95 text-on-secondary-container px-4 py-1.5 rounded-full text-label-md font-semibold backdrop-blur">
                <span class="w-2 h-2 rounded-full bg-tertiary-fixed-dim animate-pulse"></span>
                {{ $hero['hero.eyebrow'] ?? "INDONESIA'S FIRST FINANCIAL HEALTH CENTER" }}
            </span>

            <h1 class="font-display text-[32px] sm:text-[40px] md:text-[48px] lg:text-[56px] font-extrabold leading-[1.08] tracking-tight mt-6 break-words">
                {{ $hero['hero.title'] ?? 'Tubuh Bisa Sakit, Begitu Juga Dompet —' }}
                <br class="hidden md:block">
                <span class="text-secondary-fixed">Saatnya ke Dokter Finansial.</span>
            </h1>

            <p class="text-body-lg max-w-2xl mt-6 text-white/85">
                {!! nl2br(e($hero['hero.subtitle'] ?? 'Your Financial Doctor (YFD) didirikan oleh dua dokter umum yang melihat bahwa masyarakat tidak hanya butuh kesehatan jasmani, tetapi juga kesehatan finansial.')) !!}
            </p>

            <div class="flex flex-wrap gap-3 mt-10">
                <a href="{{ $primaryCheckupUrl }}" class="btn btn-gold btn-lg" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif>
                    <span class="material-symbols-outlined text-[20px]">monitor_heart</span>
                    {{ $hero['hero.cta_primary'] ?? 'Mulai Financial Health Check Up' }}
                </a>
                <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="btn btn-ghost btn-lg">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                    {{ $hero['hero.cta_secondary'] ?? 'Konsultasi Gratis via WA' }}
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 mt-10 text-[13.5px] text-white/80">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-fixed-dim text-[18px]" style="font-variation-settings:'FILL' 1;">verified</span>
                    {{ $homeCopy['home.trust_1'] ?? 'Dokter umum bersertifikat QWP' }}
                </span>
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-fixed-dim text-[18px]" style="font-variation-settings:'FILL' 1;">workspace_premium</span>
                    {{ $homeCopy['home.trust_2'] ?? 'Pendekatan medis untuk finansial' }}
                </span>
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-fixed-dim text-[18px]" style="font-variation-settings:'FILL' 1;">groups</span>
                    {{ $homeCopy['home.trust_3'] ?? 'Building Financially Healthy Generations' }}
                </span>
            </div>
        </div>
    </div>
</section>

{{-- ============== Quick Access Bar (akses cepat ke halaman penting) ============== --}}
<section class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop -mt-20 md:-mt-24 relative z-10">
    <div class="bg-white rounded-2xl shadow-lift border border-outline-variant overflow-hidden">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 divide-x divide-outline-variant md:divide-y-0 divide-y">
            @php
                // Setiap item HARUS punya halaman tersendiri biar nggak duplikat / dead-end.
                // Item pertama dihighlight jadi "pintu masuk utama" YFD.
                $quick = [
                    ['type' => 'url', 'target' => $primaryCheckupUrl, 'icon' => 'monitor_heart',        'label' => 'Health Check Up', 'badge' => 'Gratis'],
                    ['type' => 'route', 'target' => 'company.produk',      'icon' => 'send',                 'label' => 'YFD First Aid',   'badge' => 'New'],
                    ['type' => 'route', 'target' => 'company.pertemuan',   'icon' => 'forum',                'label' => 'Konsultasi'],
                    ['type' => 'route', 'target' => 'company.layanan',     'icon' => 'apps',                 'label' => 'Semua Layanan'],
                    ['type' => 'route', 'target' => 'company.penasihat',   'icon' => 'medical_information',  'label' => 'Tim Dokter'],
                    ['type' => 'route', 'target' => 'company.informasi',   'icon' => 'help',                 'label' => 'FAQ'],
                ];
            @endphp
            @foreach($quick as $q)
                @php
                    $href = '#';
                    if ($q['type'] === 'route') { try { $href = route($q['target']); } catch (\Throwable $e) {} }
                    elseif ($q['type'] === 'url') { $href = $q['target']; }
                    $isFeatured = !empty($q['badge']);
                @endphp
                <a href="{{ $href }}" @if($q['type']==='wa' || !empty($q['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                   class="relative flex flex-col items-center justify-center py-7 px-3 hover:bg-surface-container-low transition-colors group">
                    @if($isFeatured)
                        <span class="absolute top-2 right-2 text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-bold">
                            ★ {{ $q['badge'] }}
                        </span>
                    @endif
                    <span class="w-12 h-12 rounded-full grid place-items-center mb-3 transition-all
                                 {{ $isFeatured ? 'bg-secondary-container' : 'bg-primary-container/10 group-hover:bg-secondary-container' }}
                                 group-hover:scale-110">
                        <span class="material-symbols-outlined text-[22px]
                                     {{ $isFeatured ? 'text-on-secondary-container' : 'text-primary-container group-hover:text-on-secondary-container' }}">
                            {{ $q['icon'] }}
                        </span>
                    </span>
                    <span class="text-[13px] font-semibold text-primary text-center leading-tight">{{ $q['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ============== Latar Belakang Section (editable: Site Settings → Homepage) ============== --}}
<section class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-24">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <div>
            <span class="text-label-md text-secondary block mb-4">{{ $homeCopy['home.bg_eyebrow'] ?? 'LATAR BELAKANG' }}</span>
            <h2 class="font-heading text-headline-lg text-primary mb-6">
                {{ $homeCopy['home.bg_title'] ?? 'Indonesia bukan negara termiskin, tapi sebagian besar belum sehat secara finansial.' }}
            </h2>
            <div class="prose-yfd">
                <p>{!! nl2br(e($homeCopy['home.bg_p1'] ?? 'Berdasarkan data BPS 2025, 82,2% masyarakat Indonesia merupakan kelompok ekonomi menengah ke bawah. Sedikit guncangan ekonomi saja sudah berdampak luas. Akar masalahnya bukan hanya karena rendahnya literasi keuangan/pengetahuan tapi juga kurangnya regulasi diri/self awareness dalam mengambil keputusan finansial yang sehat.')) !!}</p>
                <p>{!! nl2br(e($homeCopy['home.bg_p2'] ?? 'YFD lahir untuk menjadi "dokter dompet" — membantu masyarakat memahami kondisi finansial mereka secara objektif, dan meningkatkan kekebalan komunitas (Herd Financial Immunity).')) !!}</p>
            </div>
            <a href="{{ route('company.tentang') }}" class="inline-flex items-center gap-2 mt-6 text-primary-container font-semibold text-[14px] hover:underline">
                {{ $homeCopy['home.bg_cta'] ?? 'Pelajari filosofi YFD' }} <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
        <div class="grid grid-cols-2 gap-4">
            @for($i = 1; $i <= 4; $i++)
                @php
                    $v = $stats["stats.s{$i}.value"] ?? null;
                    $l = $stats["stats.s{$i}.label"] ?? null;
                @endphp
                @if($v && $l)
                    <div class="bg-white border border-outline-variant p-6 rounded-2xl shadow-soft hover:shadow-card transition gold-border">
                        <div class="font-display text-[34px] font-extrabold text-primary-container leading-none">{{ $v }}</div>
                        <div class="text-[13px] text-on-surface-variant mt-2 leading-snug">{{ $l }}</div>
                    </div>
                @endif
            @endfor
        </div>
    </div>
</section>

{{-- ============== 6 Layanan Preview ============== --}}
<section class="bg-surface-container-low py-20 border-y border-outline-variant">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="text-center mb-12 max-w-2xl mx-auto">
            <span class="text-label-md text-secondary block mb-3">{{ $homeCopy['home.services_eyebrow'] ?? 'EKOSISTEM YFD' }}</span>
            <h2 class="font-heading text-headline-lg text-primary mb-3">{{ $homeCopy['home.services_title'] ?? 'Tujuh Layanan Kesehatan Finansial' }}</h2>
            <p class="text-body-md text-on-surface-variant">
                {{ $homeCopy['home.services_subtitle'] ?? 'Mengintegrasikan edukasi, proteksi, pendampingan, dan solusi finansial dalam satu ekosistem.' }}
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($services as $svc)
                <div class="group bg-white border border-outline-variant p-6 rounded-2xl hover:border-primary-container hover:-translate-y-1 transition-all shadow-soft hover:shadow-card">
                    <div class="w-12 h-12 bg-primary-container/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-secondary-container transition-colors">
                        <span class="material-symbols-outlined text-primary-container group-hover:text-on-secondary-container">{{ $svc->icon ?? 'medical_services' }}</span>
                    </div>
                    <h3 class="font-heading text-[19px] font-bold text-primary mb-2 leading-snug">{{ $svc->title }}</h3>
                    <p class="text-body-md text-on-surface-variant">{{ Str::limit($svc->description, 140) }}</p>
                </div>
            @empty
                <div class="md:col-span-3 text-center py-12 text-on-surface-variant italic">Layanan belum dikonfigurasi.</div>
            @endforelse
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('company.layanan') }}" class="btn btn-outline-primary">
                {{ $homeCopy['home.services_cta'] ?? 'Lihat semua layanan' }}
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

{{-- ============== Google / Testimoni (carousel) ============== --}}
<section class="bg-white py-20 border-b border-outline-variant">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="text-center mb-12 max-w-2xl mx-auto">
            <span class="text-label-md text-secondary block mb-3">TESTIMONI GOOGLE</span>
            <h2 class="font-heading text-headline-lg text-primary mb-3">
                {{ $reviews['reviews.title'] ?? 'Dipercaya Pasien Finansial' }}
            </h2>
            <p class="text-body-md text-on-surface-variant mb-5">
                {{ $reviews['reviews.subtitle'] ?? 'Baca pengalaman nyata pasien di Google — screening, konsultasi, dan pendampingan YFD.' }}
            </p>
            <div class="inline-flex flex-wrap items-center justify-center gap-2 rounded-full border border-outline-variant bg-surface-container-low px-4 py-2">
                <span class="font-heading text-[22px] text-primary leading-none">{{ $googleRating }}</span>
                <span class="inline-flex items-center gap-0.5 text-tertiary-fixed-dim" aria-hidden="true">
                    @for($s = 1; $s <= 5; $s++)
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1;">star</span>
                    @endfor
                </span>
                @if(filled($googleCount))
                    <span class="text-body-sm text-on-surface-variant">{{ $googleCount }} ulasan di Google</span>
                @endif
            </div>
        </div>

        @if($reviewItems->isNotEmpty())
            <div
                id="reviews-carousel"
                class="relative mb-10 max-w-3xl mx-auto"
                data-autoplay="6000"
                aria-roledescription="carousel"
                aria-label="Ulasan Google Your Financial Doctor"
            >
                <div class="overflow-hidden">
                    <div class="reviews-track flex transition-transform duration-500 ease-out" style="transform: translateX(0);">
                        @foreach($reviewItems as $idx => $review)
                            <blockquote
                                class="reviews-slide shrink-0 w-full px-1 box-border"
                                data-index="{{ $idx }}"
                                aria-roledescription="slide"
                                aria-label="Ulasan {{ $idx + 1 }} dari {{ $reviewItems->count() }}"
                            >
                                <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-6 md:p-8 flex flex-col min-w-0 h-full">
                                    <div class="flex items-center gap-1 mb-3 text-tertiary-fixed-dim" aria-label="Rating {{ $review['rating'] }} dari 5">
                                        @for($s = 1; $s <= 5; $s++)
                                            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1;">
                                                {{ $s <= (int) $review['rating'] ? 'star' : 'star_outline' }}
                                            </span>
                                        @endfor
                                    </div>
                                    <p class="text-body-md text-on-surface flex-grow leading-relaxed break-words min-h-[6.5rem]">“{{ $review['text'] }}”</p>
                                    <footer class="mt-5 pt-4 border-t border-outline-variant flex items-center justify-between gap-3">
                                        <cite class="not-italic font-semibold text-primary text-[14px]">{{ $review['name'] }}</cite>
                                        <span class="inline-flex items-center gap-1 text-[11px] uppercase tracking-wider text-on-surface-variant font-semibold">
                                            <span class="material-symbols-outlined text-[14px]">public</span>
                                            Google
                                        </span>
                                    </footer>
                                </div>
                            </blockquote>
                        @endforeach
                    </div>
                </div>

                @if($reviewItems->count() > 1)
                    <div class="flex items-center justify-center gap-3 mt-6">
                        <button type="button" class="reviews-prev inline-flex items-center justify-center w-11 h-11 rounded-full border border-outline-variant bg-white text-primary hover:bg-surface-container-low transition-colors" aria-label="Ulasan sebelumnya">
                            <span class="material-symbols-outlined text-[22px]">chevron_left</span>
                        </button>
                        <div class="reviews-dots flex items-center gap-2" role="tablist" aria-label="Indikator ulasan">
                            @foreach($reviewItems as $idx => $review)
                                <button
                                    type="button"
                                    class="reviews-dot h-2.5 rounded-full transition-all {{ $idx === 0 ? 'w-6 bg-primary' : 'w-2.5 bg-outline-variant' }}"
                                    data-index="{{ $idx }}"
                                    aria-label="Ke ulasan {{ $idx + 1 }}"
                                    aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"
                                ></button>
                            @endforeach
                        </div>
                        <button type="button" class="reviews-next inline-flex items-center justify-center w-11 h-11 rounded-full border border-outline-variant bg-white text-primary hover:bg-surface-container-low transition-colors" aria-label="Ulasan berikutnya">
                            <span class="material-symbols-outlined text-[22px]">chevron_right</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <div class="text-center">
            <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-lg">
                <span class="material-symbols-outlined text-[20px]">reviews</span>
                Lihat semua ulasan di Google
            </a>
        </div>
    </div>
</section>

{{-- ============== Founder Quote / Visi Block ============== --}}
<section class="relative bg-primary text-on-primary py-24 overflow-hidden">
    <div class="absolute inset-0 bg-hero-radial"></div>
    <div class="relative max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop text-center">
        <span class="material-symbols-outlined text-secondary-fixed text-[56px] mb-4">format_quote</span>
        <p class="font-heading text-headline-lg max-w-3xl mx-auto mb-8 italic">
            "Membangun sistem finansial yang sehat dimulai dari membangun manusia yang sehat."
        </p>
        <p class="text-body-md opacity-80 mb-1">— Filosofi YFD</p>
        <p class="text-caption opacity-60">
            Founder: dr. Ayuti Bulaan QWP &nbsp;·&nbsp; Co-Founder: dr. Catherine QWP
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-3 mt-10">
            <a href="{{ route('company.tentang') }}" class="btn btn-gold btn-lg justify-center">
                Pelajari Visi & Misi
            </a>
            <a href="{{ route('company.penasihat') }}" class="btn btn-ghost btn-lg justify-center">
                Kenali Tim Dokter
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
@if(isset($reviewItems) && $reviewItems->count() > 1)
<script>
(function () {
    var root = document.getElementById('reviews-carousel');
    if (!root) return;

    var track = root.querySelector('.reviews-track');
    var slides = Array.prototype.slice.call(root.querySelectorAll('.reviews-slide'));
    var dots = Array.prototype.slice.call(root.querySelectorAll('.reviews-dot'));
    var prevBtn = root.querySelector('.reviews-prev');
    var nextBtn = root.querySelector('.reviews-next');
    var autoplayMs = parseInt(root.getAttribute('data-autoplay') || '6000', 10);
    var index = 0;
    var timer = null;
    var total = slides.length;

    function goTo(next) {
        index = ((next % total) + total) % total;
        track.style.transform = 'translateX(' + (-index * 100) + '%)';
        dots.forEach(function (dot, i) {
            var active = i === index;
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
            dot.classList.toggle('w-6', active);
            dot.classList.toggle('bg-primary', active);
            dot.classList.toggle('w-2.5', !active);
            dot.classList.toggle('bg-outline-variant', !active);
        });
    }

    function next() { goTo(index + 1); }
    function prev() { goTo(index - 1); }

    function startAutoplay() {
        stopAutoplay();
        if (autoplayMs > 0 && total > 1) timer = setInterval(next, autoplayMs);
    }
    function stopAutoplay() {
        if (timer) { clearInterval(timer); timer = null; }
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); startAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { next(); startAutoplay(); });
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            goTo(parseInt(dot.getAttribute('data-index') || '0', 10));
            startAutoplay();
        });
    });

    root.addEventListener('mouseenter', stopAutoplay);
    root.addEventListener('mouseleave', startAutoplay);
    root.addEventListener('focusin', stopAutoplay);
    root.addEventListener('focusout', startAutoplay);

    var startX = 0;
    var deltaX = 0;
    track.addEventListener('touchstart', function (e) {
        startX = e.touches[0].clientX;
        deltaX = 0;
        stopAutoplay();
    }, { passive: true });
    track.addEventListener('touchmove', function (e) {
        deltaX = e.touches[0].clientX - startX;
    }, { passive: true });
    track.addEventListener('touchend', function () {
        if (Math.abs(deltaX) > 40) {
            if (deltaX < 0) next(); else prev();
        }
        startAutoplay();
    });

    goTo(0);
    startAutoplay();
})();
</script>
@endif
@endpush
