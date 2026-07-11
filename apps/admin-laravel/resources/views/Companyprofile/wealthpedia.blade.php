@extends('Companyprofile.layouts.main')

@section('title', 'Wealthpedia — Literasi Finansial YFD')

@section('content')

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

    {{-- ============== Hero ============== --}}
    <header class="mb-16 text-center">
        <span class="font-label-md text-label-md text-secondary tracking-widest block mb-3">EDUCATION PLATFORM</span>
        <h1 class="font-display-lg text-display-lg text-primary mb-6 max-w-4xl mx-auto">
            Wealthpedia: Literasi Finansial Adalah Hak, Bukan Privilese.
        </h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto mb-8">
            Pusat edukasi praktis YFD — webinar, e-book, social media education, dan artikel mendalam
            tentang practical financial literacy &amp; emotional finance.
        </p>

        {{-- Search Bar --}}
        <form class="max-w-2xl mx-auto" action="#" method="GET">
            <label for="searchInput" class="sr-only">Cari artikel</label>
            <div class="relative">
                <input id="searchInput" type="text" name="q" placeholder="Cari topik: hutang, dana darurat, investasi pemula..."
                       class="w-full pl-14 pr-32 py-4 bg-surface-container-lowest border border-outline-variant rounded-full font-body-md text-body-md focus:ring-2 focus:ring-primary-container focus:border-primary-container">
                <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-primary-container text-on-primary px-5 py-2 rounded-full font-label-md text-label-md hover:opacity-90">
                    Cari
                </button>
            </div>
        </form>
    </header>

    {{-- ============== Kategori dari artikel aktif ============== --}}
    <section class="mb-20">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary mb-2">Kategori Edukasi</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Kategori mengikuti field kategori saat upload artikel di admin.</p>
            </div>
            @if(!empty($activeCategory))
                <a href="{{ route('company.wealthpedia') }}" class="font-label-md text-label-md text-primary-container hover:underline">Lihat semua</a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
            @forelse(($categories ?? []) as $cat)
                <a href="{{ route('company.wealthpedia', ['category' => $cat['name']]) }}"
                   class="group bg-surface-container-lowest border rounded-xl p-6 transition-colors {{ ($activeCategory ?? '') === $cat['name'] ? 'border-primary-container ring-1 ring-primary-container' : 'border-outline-variant hover:border-primary-container' }}">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-primary-container/10 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary-container">{{ $cat['ic'] }}</span>
                        </div>
                        <span class="font-caption text-caption text-on-surface-variant">{{ $cat['count'] }} artikel</span>
                    </div>
                    <h3 class="font-headline-md text-[18px] font-bold text-primary mb-2 group-hover:text-primary-container transition-colors">{{ $cat['title'] }}</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant">{{ $cat['desc'] }}</p>
                </a>
            @empty
                <div class="md:col-span-3 text-center py-8 text-on-surface-variant italic">
                    Belum ada kategori. Isi field <strong>Kategori</strong> saat upload artikel di admin.
                </div>
            @endforelse
        </div>
    </section>

    {{-- ============== Featured Articles ============== --}}
    <section class="mb-20">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary mb-2">Artikel Pilihan</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Topik paling banyak dibaca minggu ini.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
            @forelse($articles as $article)
                <a href="{{ route('company.wealthpedia.show', $article->slug) }}"
                   class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden hover:border-primary-container transition-colors flex flex-col group">
                    <div class="aspect-video bg-primary-container/10 flex items-center justify-center overflow-hidden">
                        @if($article->image_url)
                            <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                        @else
                            <span class="material-symbols-outlined text-primary-container text-6xl">article</span>
                        @endif
                    </div>
                    <div class="p-6 flex-grow flex flex-col">
                        @if($article->category)
                            <span class="font-label-md text-label-md text-secondary mb-2 tracking-wider uppercase">{{ $article->category }}</span>
                        @endif
                        <h3 class="font-headline-md text-[18px] font-bold text-primary mb-2 leading-snug group-hover:text-primary-container transition-colors">{{ $article->title }}</h3>
                        @if($article->description)
                            <p class="font-body-md text-body-md text-on-surface-variant flex-grow mb-4">{{ Str::limit($article->description, 130) }}</p>
                        @endif
                        <div class="flex items-center justify-between border-t border-outline-variant pt-4 mt-auto">
                            @if($article->read_time)
                                <span class="font-caption text-caption text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">schedule</span> {{ $article->read_time }}
                                </span>
                            @else
                                <span></span>
                            @endif
                            <span class="font-label-md text-label-md text-primary-container group-hover:underline">Baca →</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="md:col-span-3 text-center py-12 text-on-surface-variant italic">Belum ada artikel.</div>
            @endforelse
        </div>
    </section>

    {{-- ============== Channels ============== --}}
    <section class="bg-primary-container text-on-primary rounded-2xl p-10 mb-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            <div>
                <span class="font-label-md text-label-md text-secondary-fixed tracking-widest block mb-3">FOLLOW US</span>
                <h2 class="font-headline-lg text-headline-lg mb-4">Mini-Edukasi Harian di Social Media</h2>
                <p class="font-body-md text-body-md opacity-90 mb-6">
                    Konten singkat, praktis, dan langsung bisa dipraktikkan — terbit reguler di Instagram, TikTok, dan Threads YFD.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ \App\Support\SocialUrl::instagram($yfd['instagram']) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 bg-white text-primary-container px-6 py-3 rounded font-label-md text-label-md hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined">photo_camera</span> Instagram
                    </a>
                    <a href="{{ \App\Support\SocialUrl::tiktok($yfd['tiktok']) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 bg-black text-white px-6 py-3 rounded font-label-md text-label-md hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined">music_note</span> TikTok
                    </a>
                    <a href="{{ \App\Support\SocialUrl::threads($yfd['threads']) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 bg-[#101010] text-white border border-white/15 px-6 py-3 rounded font-label-md text-label-md hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined">alternate_email</span> Threads
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['ic' => 'video_camera_front', 'l' => 'Webinar'],
                    ['ic' => 'menu_book',          'l' => 'E-book'],
                    ['ic' => 'school',             'l' => 'Kelas Online'],
                    ['ic' => 'forum',              'l' => 'Community Learning'],
                ] as $ch)
                    <div class="bg-white/10 border border-white/20 p-5 rounded-lg">
                        <span class="material-symbols-outlined text-secondary-fixed text-3xl mb-2">{{ $ch['ic'] }}</span>
                        <p class="font-label-md text-label-md">{{ $ch['l'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============== CTA ============== --}}
    <div class="text-center">
        <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Edukasi Saja Belum Cukup?</h2>
        <p class="font-body-md text-body-md text-on-surface-variant mb-6 max-w-xl mx-auto">
            Untuk diagnosis yang personal, lakukan Financial Health Check Up bersama tim YFD.
        </p>
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-center gap-3 mx-auto w-fit max-w-full">
            <a href="{{ $primaryCheckupUrl }}" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif class="inline-flex items-center justify-center text-center bg-primary-container text-on-primary px-8 py-3 rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Mulai Health Check Up
            </a>
            <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center gap-2 border border-primary-container text-primary-container px-8 py-3 rounded-lg font-label-md text-label-md hover:bg-primary-container/5 transition-colors">
                <span class="material-symbols-outlined">chat</span> Chat Tim YFD
            </a>
        </div>
    </div>

</main>
@endsection
