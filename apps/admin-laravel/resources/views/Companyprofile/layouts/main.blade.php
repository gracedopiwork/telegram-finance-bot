@php
    $active = $active ?? '';

    // Item dropdown "Layanan" — kolom kiri = jasa (perlu interaksi manusia),
    // kolom kanan = produk digital self-serve (diisi dari admin: Produk Digital → Featured).
    $digitalMenuItems = app(\App\Services\DigitalProductMenuService::class)->featuredMenuItems();
    $servicesMenu = [
        'jasa' => [
            'title' => 'Jasa & Pendampingan',
            'icon'  => 'stethoscope',
            'items' => [
                ['key' => 'paket',     'label' => 'Health Check Up',       'desc' => 'Screening kesehatan finansial gratis — 3–5 menit',       'route' => 'checkup.show',              'icon' => 'monitor_heart', 'badge' => 'Gratis'],
                ['key' => 'pertemuan', 'label' => 'Konsultasi 1-on-1',     'desc' => 'Sesi privat dengan dokter QWP',                          'route' => 'company.pertemuan',         'icon' => 'forum',         'badge' => null],
                ['key' => 'recovery',  'label' => 'Recovery Program',      'desc' => 'Pendampingan intensif untuk kondisi finansial darurat',  'route' => 'company.bundle.recovery',   'icon' => 'healing', 'badge' => null],
                ['key' => 'edukasi',   'label' => 'Education Platform',    'desc' => 'Webinar, kelas online, e-book & Wealthpedia',            'route' => 'company.bundle.education',  'icon' => 'school',        'badge' => null],
            ],
            'cta' => ['label' => 'Lihat semua 6 pilar layanan', 'route' => 'company.layanan'],
        ],
        'digital' => [
            'title' => 'Produk Digital',
            'icon'  => 'auto_awesome',
            'items' => $digitalMenuItems,
            'cta' => ['label' => 'Lihat semua produk digital', 'route' => 'company.produk'],
        ],
    ];

    // Sub-menu untuk dropdown "Tentang" (single column, sederhana)
    $aboutMenu = [
        ['key' => 'tentang',   'label' => 'Tentang YFD',     'desc' => 'Latar belakang, visi & misi, filosofi', 'route' => 'company.tentang',   'icon' => 'info'],
        ['key' => 'penasihat', 'label' => 'Tim Dokter',      'desc' => 'Profil dokter QWP & penasihat',         'route' => 'company.penasihat', 'icon' => 'medical_information'],
    ];

    // Top-nav simpel — 5 item saja
    $nav = [
        ['key' => 'home',         'label' => 'Home',         'route' => 'company.home',        'kind' => 'link'],
        ['key' => 'tentang-grp',  'label' => 'Tentang',      'route' => null,                  'kind' => 'dropdown'],
        ['key' => 'layanan-grp',  'label' => 'Layanan',      'route' => null,                  'kind' => 'mega'],
        ['key' => 'wealthpedia',  'label' => 'Wealthpedia',  'route' => 'company.wealthpedia', 'kind' => 'link'],
        ['key' => 'informasi',    'label' => 'Informasi',    'route' => 'company.informasi',   'kind' => 'link'],
    ];

    // Active grup
    $serviceKeys = ['layanan', 'paket', 'pertemuan', 'recovery', 'edukasi'];
    $aboutKeys   = ['tentang', 'penasihat'];
    $isServiceActive = in_array($active, $serviceKeys, true);
    $isAboutActive   = in_array($active, $aboutKeys,   true);
    // $yfd & $waBookingUrl di-share otomatis dari AppServiceProvider (data di-pull dari Setting)
@endphp
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>@yield('title', 'Your Financial Doctor — Indonesia\'s First Financial Health Center')</title>
    <meta name="description" content="@yield('description', 'YFD adalah pusat kesehatan finansial pertama di Indonesia, didirikan oleh dokter umum yang melihat bahwa dompet yang sakit juga butuh dokter. Building Financially Healthy Generations.')">
    <meta name="google-site-verification" content="wLl4Mbp-UkmlhXLljmBiWlNZg3HtZy5kjb4PF3ThxG8">
    @if($canonical = rtrim((string) config('app.url'), '/'))
        <link rel="canonical" href="{{ $canonical }}{{ request()->getPathInfo() === '/' ? '/' : request()->getPathInfo() }}">
    @endif
    <link rel="icon" type="image/png" href="{{ asset($yfd['logo'] ?? 'images/yfd-logo.png') }}">

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries,line-clamp"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        @include('partials.yfd-tailwind-colors')
                        // Material You alias (YFD client palette)
                        primary:                "{{ config('yfd_brand.navy') }}",
                        "primary-container":    "{{ config('yfd_brand.navy') }}",
                        "on-primary":           "{{ config('yfd_brand.white') }}",
                        "on-primary-container": "{{ config('yfd_brand.mint_light') }}",

                        secondary:                "{{ config('yfd_brand.mint') }}",
                        "secondary-container":    "{{ config('yfd_brand.mint_light') }}",
                        "secondary-fixed":        "{{ config('yfd_brand.mint_light') }}",
                        "secondary-fixed-dim":    "{{ config('yfd_brand.mint') }}",
                        "on-secondary":           "{{ config('yfd_brand.white') }}",
                        "on-secondary-container": "{{ config('yfd_brand.navy') }}",
                        "on-secondary-fixed":     "{{ config('yfd_brand.navy') }}",
                        "on-secondary-fixed-variant": "{{ config('yfd_brand.navy_700') }}",

                        tertiary:               "{{ config('yfd_brand.gold_dark') }}",
                        "tertiary-container":   "{{ config('yfd_brand.gold') }}",
                        "tertiary-fixed":       "{{ config('yfd_brand.gold') }}",
                        "tertiary-fixed-dim":   "{{ config('yfd_brand.gold_dark') }}",
                        "on-tertiary":          "{{ config('yfd_brand.navy') }}",

                        background:                  "#f8fafc",
                        surface:                     "#ffffff",
                        "surface-bright":            "#ffffff",
                        "surface-variant":           "#eef2f6",
                        "surface-container-lowest":  "#ffffff",
                        "surface-container-low":     "#f4f6f8",
                        "surface-container":         "#eef1f4",
                        "surface-container-high":    "#e6eaee",
                        "surface-container-highest": "#dde1e6",

                        outline:           "#94a3b8",
                        "outline-variant": "#cbd5e1",

                        "on-surface":          "#0f172a",
                        "on-surface-variant":  "#475569",
                        "on-background":       "#0f172a",

                        error:                  "#ba1a1a",
                        "error-container":      "#ffdad6",
                        "on-error":             "#ffffff",
                        "on-error-container":   "#93000a"
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                        sm: "0.375rem",
                        md: "0.5rem",
                        lg: "0.75rem",
                        xl: "1rem",
                        "2xl": "1.5rem",
                        "3xl": "2rem",
                        full: "9999px"
                    },
                    spacing: {
                        "container-max": "1280px",
                        "margin-desktop": "32px",
                        "unit": "8px",
                        "gutter": "24px",
                        "margin-mobile": "16px"
                    },
                    fontFamily: {
                        display:  ["Manrope","ui-sans-serif","system-ui"],
                        heading:  ["Manrope","ui-sans-serif","system-ui"],
                        body:     ["Inter","ui-sans-serif","system-ui"]
                    },
                    fontSize: {
                        "display-xl": ["64px", { lineHeight: "72px", letterSpacing: "-0.025em", fontWeight: "800" }],
                        "display-lg": ["48px", { lineHeight: "56px", letterSpacing: "-0.02em",  fontWeight: "800" }],
                        "headline-lg": ["36px", { lineHeight: "44px", letterSpacing: "-0.01em", fontWeight: "700" }],
                        "headline-md": ["24px", { lineHeight: "32px", fontWeight: "700" }],
                        "body-lg":    ["18px", { lineHeight: "30px", fontWeight: "400" }],
                        "body-md":    ["16px", { lineHeight: "26px", fontWeight: "400" }],
                        "label-md":   ["14px", { lineHeight: "20px", letterSpacing: "0.06em", fontWeight: "600" }],
                        "caption":    ["12px", { lineHeight: "16px", fontWeight: "500" }]
                    },
                    boxShadow: {
                        "soft":   "0 1px 2px rgba(13, 43, 78, .04), 0 4px 12px rgba(13, 43, 78, .04)",
                        "card":   "0 4px 20px rgba(13, 43, 78, .08)",
                        "lift":   "0 12px 40px rgba(13, 43, 78, .12)"
                    },
                    backgroundImage: {
                        "hero-grid":     "linear-gradient(rgba(255,255,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.05) 1px,transparent 1px)",
                        "hero-radial":   "radial-gradient(60% 80% at 80% 20%, rgba(245,166,35,.18), transparent 60%), radial-gradient(50% 80% at 0% 100%, rgba(79,195,161,.18), transparent 60%)"
                    }
                }
            }
        }
    </script>

    <style>
        html, body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, .font-heading, .font-display { font-family: 'Manrope', sans-serif; letter-spacing: -0.01em; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }

        /* button base */
        .btn {
            display: inline-flex; align-items: center; gap: .5rem; white-space: nowrap;
            padding: .6rem 1.1rem; border-radius: 9999px; font-weight: 600; font-size: 14px;
            transition: transform .15s ease, box-shadow .2s ease, background-color .2s ease, color .2s ease;
            line-height: 1;
        }
        .btn-primary { background: {{ config('yfd_brand.navy') }}; color: #fff; box-shadow: 0 6px 18px rgba(13,43,78,.18); }
        .btn-primary:hover { background: {{ config('yfd_brand.navy_700') }}; transform: translateY(-1px); box-shadow: 0 10px 24px rgba(13,43,78,.25); }
        .btn-gold { background: {{ config('yfd_brand.gold') }}; color: {{ config('yfd_brand.navy') }}; box-shadow: 0 6px 18px rgba(245,166,35,.35); }
        .btn-gold:hover { background: {{ config('yfd_brand.gold_dark') }}; transform: translateY(-1px); }
        .btn-ghost { color: #fff; border: 1px solid rgba(255,255,255,.35); background: transparent; }
        .btn-ghost:hover { background: rgba(255,255,255,.12); }
        .btn-wa { background: #25D366; color: #fff; box-shadow: 0 6px 16px rgba(37,211,102,.35); }
        .btn-wa:hover { background: #1ebe5b; }
        .btn-outline-primary { color:{{ config('yfd_brand.navy') }}; border:1px solid {{ config('yfd_brand.navy') }}; background:transparent;}
        .btn-outline-primary:hover { background:{{ config('yfd_brand.navy') }}; color:#fff; }
        .btn-lg { padding: .9rem 1.6rem; font-size: 15px; }

        /* nav active underline */
        .nav-link {
            position: relative; padding: 8px 4px; font-weight: 500; color: #475569;
            transition: color .15s ease;
        }
        .nav-link:hover { color: {{ config('yfd_brand.navy') }}; }
        .nav-link.active { color: {{ config('yfd_brand.navy') }}; font-weight: 700; }
        .nav-link.active::after {
            content:""; position: absolute; left:0; right:0; bottom: -2px; height: 3px;
            border-radius: 3px; background: {{ config('yfd_brand.gold') }};
        }

        .clinical-shadow { box-shadow: 0 4px 20px rgba(0,51,102,.08); }
        .gold-border { border-left: 4px solid {{ config('yfd_brand.gold') }}; }

        /* prose-ish utilities */
        .prose-yfd p { margin-bottom: 1rem; line-height: 1.75; color: #334155; }
        .prose-yfd strong { color: {{ config('yfd_brand.navy') }}; }
        .prose-yfd ul { list-style: disc; padding-left: 1.25rem; }

        /* keep tagline single line */
        .brand-tagline { white-space: nowrap; }
    </style>
    @stack('head')
</head>
<body class="bg-background text-on-surface antialiased">

{{-- ============== TopNavBar ============== --}}
<header class="sticky top-0 z-50 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/75 border-b border-outline-variant">
    <div class="max-w-container-max mx-auto w-full px-margin-mobile md:px-margin-desktop h-16 md:h-20 flex items-center justify-between gap-4">

        {{-- Logo + brand --}}
        <a href="{{ route('company.home') }}" class="flex items-center gap-3 shrink-0">
            <img alt="{{ $yfd['brand'] }} Logo" class="h-9 md:h-11 w-auto" src="{{ asset($yfd['logo'] ?? 'images/yfd-logo.png') }}">
            <div class="hidden sm:block leading-tight">
                <div class="text-[15px] md:text-[17px] font-extrabold text-primary-container tracking-tight">{{ $yfd['brand'] }}</div>
                <div class="text-[11px] md:text-caption text-on-surface-variant brand-tagline">{{ $yfd['tagline'] }}</div>
            </div>
        </a>

        {{-- Desktop nav --}}
        <nav class="hidden lg:flex items-center gap-7" id="desktopNav">
            @foreach($nav as $item)
                @if($item['kind'] === 'dropdown')
                    {{-- Dropdown sederhana 1-kolom (Tentang) --}}
                    <div class="relative" data-megamenu>
                        <button type="button"
                                class="nav-link text-[14px] inline-flex items-center gap-1 {{ $isAboutActive ? 'active' : '' }}"
                                data-megamenu-trigger
                                aria-expanded="false">
                            {{ $item['label'] }}
                            <span class="material-symbols-outlined text-[18px] transition-transform" data-megamenu-chevron>expand_more</span>
                        </button>
                        {{-- Outer wrapper: punya pt-3 sebagai 'bridge' transparan supaya cursor tidak putus --}}
                        <div class="hidden absolute top-full left-1/2 -translate-x-1/2 pt-3 z-50"
                             data-megamenu-panel>
                            <div class="w-[320px] bg-white rounded-2xl shadow-lift border border-outline-variant overflow-hidden">
                                <ul class="p-2">
                                    @foreach($aboutMenu as $sub)
                                        @php
                                            $href = '#';
                                            try { $href = route($sub['route']); } catch (\Throwable $e) {}
                                            $isActiveSub = ($active === $sub['key']);
                                        @endphp
                                        <li>
                                            <a href="{{ $href }}"
                                               class="flex items-start gap-3 p-3 rounded-xl hover:bg-surface-container-low transition-colors group {{ $isActiveSub ? 'bg-primary-container/5' : '' }}">
                                                <span class="w-9 h-9 rounded-lg bg-primary-container/10 grid place-items-center flex-shrink-0 group-hover:bg-secondary-container transition-colors">
                                                    <span class="material-symbols-outlined text-primary-container text-[20px] group-hover:text-on-secondary-container">{{ $sub['icon'] }}</span>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block font-semibold text-[14px] text-primary">{{ $sub['label'] }}</span>
                                                    <span class="block text-[12.5px] text-on-surface-variant leading-snug mt-0.5">{{ $sub['desc'] }}</span>
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @elseif($item['kind'] === 'mega')
                    <div class="relative" data-megamenu>
                        <button type="button"
                                class="nav-link text-[14px] inline-flex items-center gap-1 {{ $isServiceActive ? 'active' : '' }}"
                                data-megamenu-trigger
                                aria-expanded="false">
                            {{ $item['label'] }}
                            <span class="material-symbols-outlined text-[18px] transition-transform" data-megamenu-chevron>expand_more</span>
                        </button>

                        {{-- Mega menu panel — outer pt-3 = bridge transparan (no dead zone) --}}
                        <div class="hidden absolute top-full left-1/2 -translate-x-1/2 pt-3 z-50"
                             data-megamenu-panel>
                            <div class="w-[680px] bg-white rounded-2xl shadow-lift border border-outline-variant overflow-hidden">
                            <div class="grid grid-cols-2 divide-x divide-outline-variant">
                                @foreach($servicesMenu as $col)
                                    <div class="p-5">
                                        <div class="flex items-center gap-2 mb-4 px-2">
                                            <span class="material-symbols-outlined text-secondary text-[18px]">{{ $col['icon'] }}</span>
                                            <span class="text-[11px] font-bold uppercase tracking-widest text-secondary">{{ $col['title'] }}</span>
                                        </div>
                                        <ul class="space-y-1">
                                            @foreach($col['items'] as $sub)
                                                @php
                                                    $href = '#';
                                                    if (!empty($sub['route'])) {
                                                        try {
                                                            $href = route($sub['route'], $sub['query'] ?? []);
                                                        } catch (\Throwable $e) {}
                                                    }
                                                    elseif (!empty($sub['url'])) { $href = $sub['url']; }
                                                    $isDisabled = $href === '#' || $href === null;
                                                @endphp
                                                <li>
                                                    <a href="{{ $href }}"
                                                       @if(!empty($sub['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                                                       class="flex items-start gap-3 p-3 rounded-xl hover:bg-surface-container-low transition-colors group {{ $isDisabled ? 'pointer-events-none opacity-60' : '' }}">
                                                        <span class="w-9 h-9 rounded-lg bg-primary-container/10 grid place-items-center flex-shrink-0 group-hover:bg-secondary-container transition-colors">
                                                            <span class="material-symbols-outlined text-primary-container text-[20px] group-hover:text-on-secondary-container">{{ $sub['icon'] }}</span>
                                                        </span>
                                                        <span class="min-w-0">
                                                            <span class="flex items-center gap-2">
                                                                <span class="font-semibold text-[14px] text-primary">{{ $sub['label'] }}</span>
                                                                @if(!empty($sub['badge']))
                                                                    <span class="text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded-full
                                                                        {{ str_contains(strtolower($sub['badge']), 'soon')
                                                                              ? 'bg-surface-container-high text-on-surface-variant'
                                                                              : 'bg-secondary-container text-on-secondary-container' }}">
                                                                        {{ $sub['badge'] }}
                                                                    </span>
                                                                @endif
                                                            </span>
                                                            <span class="block text-[12.5px] text-on-surface-variant leading-snug mt-0.5">{{ $sub['desc'] }}</span>
                                                        </span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if(!empty($col['cta']))
                                            <a href="{{ route($col['cta']['route']) }}"
                                               class="mt-3 inline-flex items-center gap-1 text-[13px] font-semibold text-primary-container hover:underline px-3">
                                                {{ $col['cta']['label'] }}
                                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="bg-surface-container-low border-t border-outline-variant px-5 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <span class="text-[12.5px] text-on-surface-variant">
                                    <span class="material-symbols-outlined text-secondary text-[16px] align-middle">tips_and_updates</span>
                                    Belum tahu mulai dari mana? Mulai dengan <strong>Health Check Up</strong>.
                                </span>
                                <div class="flex flex-wrap items-center gap-4">
                                    <a href="{{ $primaryCheckupUrl }}" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif
                                       class="text-[13px] font-semibold text-primary-container hover:underline whitespace-nowrap">
                                        Mulai Health Check Up →
                                    </a>
                                    <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
                                       class="text-[13px] font-semibold text-primary-container hover:underline whitespace-nowrap">
                                        Tanya tim YFD →
                                    </a>
                                </div>
                            </div>
                            </div>{{-- /panel inner --}}
                        </div>{{-- /panel outer with bridge --}}
                    </div>
                @else
                    <a href="{{ route($item['route']) }}"
                       class="nav-link text-[14px] {{ $active === $item['key'] ? 'active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        {{-- CTA --}}
        <div class="flex items-center gap-2 md:gap-3 shrink-0">
            <a href="{{ route('portal.login') }}"
               class="hidden md:inline-flex btn btn-outline-primary">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                Login Dashboard
            </a>
            <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
               class="hidden md:inline-flex btn btn-gold">
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1;">chat</span>
                Konsultasi WA
            </a>
            <a href="{{ route('company.pertemuan') }}" class="hidden sm:inline-flex btn btn-primary">
                Booking
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
            <button id="mobileMenuBtn" type="button" class="lg:hidden w-10 h-10 grid place-items-center text-primary-container rounded-lg hover:bg-surface-container-low" aria-label="Toggle menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobileMenu" class="lg:hidden hidden border-t border-outline-variant bg-white max-h-[calc(100vh-4rem)] overflow-y-auto">
        <nav class="px-margin-mobile py-4 grid gap-1 text-[15px]">
            @foreach($nav as $item)
                @if($item['kind'] === 'dropdown')
                    <details class="group" {{ $isAboutActive ? 'open' : '' }}>
                        <summary class="list-none cursor-pointer px-3 py-2.5 rounded-lg flex items-center justify-between
                                        {{ $isAboutActive ? 'bg-primary-container/10 text-primary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                            <span>{{ $item['label'] }}</span>
                            <span class="material-symbols-outlined text-[18px] opacity-60 transition-transform group-open:rotate-180">expand_more</span>
                        </summary>
                        <div class="mt-1 mb-2 ml-3 pl-3 border-l border-outline-variant py-1">
                            @foreach($aboutMenu as $sub)
                                @php
                                    $href = '#';
                                    try { $href = route($sub['route']); } catch (\Throwable $e) {}
                                @endphp
                                <a href="{{ $href }}" class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container-low">
                                    <span class="material-symbols-outlined text-primary-container text-[20px]">{{ $sub['icon'] }}</span>
                                    <span class="flex-1">
                                        <span class="font-semibold text-[14px] text-primary">{{ $sub['label'] }}</span>
                                        <span class="block text-[12px] text-on-surface-variant leading-snug">{{ $sub['desc'] }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @elseif($item['kind'] === 'mega')
                    <details class="group" {{ $isServiceActive ? 'open' : '' }}>
                        <summary class="list-none cursor-pointer px-3 py-2.5 rounded-lg flex items-center justify-between
                                        {{ $isServiceActive ? 'bg-primary-container/10 text-primary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                            <span>{{ $item['label'] }}</span>
                            <span class="material-symbols-outlined text-[18px] opacity-60 transition-transform group-open:rotate-180">expand_more</span>
                        </summary>
                        <div class="mt-1 mb-2 ml-3 pl-3 border-l border-outline-variant space-y-3 py-2">
                            @foreach($servicesMenu as $col)
                                <div>
                                    <div class="flex items-center gap-2 mb-2 px-2">
                                        <span class="material-symbols-outlined text-secondary text-[16px]">{{ $col['icon'] }}</span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-secondary">{{ $col['title'] }}</span>
                                    </div>
                                    @foreach($col['items'] as $sub)
                                        @php
                                            $href = '#';
                                            if (!empty($sub['route'])) {
                                                try {
                                                    $href = route($sub['route'], $sub['query'] ?? []);
                                                } catch (\Throwable $e) {}
                                            }
                                            elseif (!empty($sub['url'])) { $href = $sub['url']; }
                                            $isDisabled = $href === '#' || $href === null;
                                        @endphp
                                        <a href="{{ $href }}"
                                           @if(!empty($sub['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                                           class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-surface-container-low {{ $isDisabled ? 'pointer-events-none opacity-60' : '' }}">
                                            <span class="material-symbols-outlined text-primary-container text-[20px]">{{ $sub['icon'] }}</span>
                                            <span class="flex-1">
                                                <span class="font-semibold text-[14px] text-primary">{{ $sub['label'] }}</span>
                                                @if(!empty($sub['badge']))
                                                    <span class="ml-1.5 text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded-full
                                                        {{ str_contains(strtolower($sub['badge']), 'soon')
                                                              ? 'bg-surface-container-high text-on-surface-variant'
                                                              : 'bg-secondary-container text-on-secondary-container' }}">
                                                        {{ $sub['badge'] }}
                                                    </span>
                                                @endif
                                                <span class="block text-[12px] text-on-surface-variant leading-snug">{{ $sub['desc'] }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </details>
                @else
                    <a href="{{ route($item['route']) }}"
                       class="px-3 py-2.5 rounded-lg flex items-center justify-between
                              {{ $active === $item['key']
                                    ? 'bg-primary-container/10 text-primary-container font-semibold'
                                    : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                        <span>{{ $item['label'] }}</span>
                        <span class="material-symbols-outlined text-[18px] opacity-50">chevron_right</span>
                    </a>
                @endif
            @endforeach
            <div class="grid grid-cols-2 gap-2 mt-3">
                <a href="{{ route('portal.login') }}" class="btn btn-outline-primary justify-center col-span-2">
                    <span class="material-symbols-outlined text-[18px]">dashboard</span>
                    Login Dashboard
                </a>
                <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="btn btn-gold justify-center">
                    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1;">chat</span>
                    WA
                </a>
                <a href="{{ route('company.pertemuan') }}" class="btn btn-primary justify-center">
                    Booking
                </a>
            </div>
        </nav>
    </div>
</header>

{{-- ============== Flash Messages ============== --}}
@if(session('success') || $errors->any())
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop pt-6">
        @if(session('success'))
            <div class="bg-secondary-fixed/40 border border-secondary/40 text-on-secondary-fixed-variant px-4 py-3 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="text-body-md">{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="mt-3 bg-error-container border border-error/40 text-on-error-container px-4 py-3 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined">error</span>
                <span class="text-body-md">{{ $errors->first() }}</span>
            </div>
        @endif
    </div>
@endif

{{-- ============== Main Content ============== --}}
<main>
    @yield('content')
</main>

{{-- ============== Footer ============== --}}
<footer class="bg-primary-container text-on-primary mt-0 relative overflow-hidden">
    <div class="absolute inset-0 bg-hero-radial opacity-60 pointer-events-none"></div>

    <div class="relative max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-16 grid grid-cols-1 md:grid-cols-12 gap-10">
        <div class="md:col-span-5">
            <div class="flex items-center gap-3 mb-5">
                <img alt="{{ $yfd['brand'] }} Logo" class="h-12 w-auto" src="{{ asset($yfd['logo_footer'] ?? $yfd['logo'] ?? 'images/yfd-logo.png') }}">
                <div class="leading-tight">
                    <div class="text-[20px] font-extrabold tracking-tight">{{ $yfd['brand'] }}</div>
                    <div class="text-caption opacity-70">{{ $yfd['tagline'] }}</div>
                </div>
            </div>
            <p class="text-body-md opacity-80 max-w-md mb-5">
                Pusat kesehatan finansial pertama di Indonesia. Didirikan oleh dokter umum yang melihat
                bahwa dompet yang sakit juga butuh dokter.
            </p>
            <p class="text-label-md italic text-secondary-fixed">"{{ $yfd['motto'] }}"</p>

            <div class="flex items-center gap-3 mt-6">
                <a href="{{ \App\Support\SocialUrl::instagram($yfd['instagram']) }}" target="_blank" rel="noopener"
                   class="w-10 h-10 grid place-items-center rounded-full bg-white/10 hover:bg-secondary-container/90 hover:text-on-secondary-container transition-colors" aria-label="Instagram">
                    <span class="material-symbols-outlined text-[20px]">photo_camera</span>
                </a>
                <a href="{{ \App\Support\SocialUrl::tiktok($yfd['tiktok']) }}" target="_blank" rel="noopener"
                   class="w-10 h-10 grid place-items-center rounded-full bg-white/10 hover:bg-secondary-container/90 hover:text-on-secondary-container transition-colors" aria-label="TikTok">
                    <span class="material-symbols-outlined text-[20px]">music_note</span>
                </a>
                <a href="{{ \App\Support\SocialUrl::threads($yfd['threads']) }}" target="_blank" rel="noopener"
                   class="w-10 h-10 grid place-items-center rounded-full bg-white/10 hover:bg-secondary-container/90 hover:text-on-secondary-container transition-colors" aria-label="Threads">
                    <span class="material-symbols-outlined text-[20px]">alternate_email</span>
                </a>
                <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
                   class="w-10 h-10 grid place-items-center rounded-full bg-white/10 hover:bg-[#25D366] transition-colors" aria-label="WhatsApp">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                </a>
            </div>
        </div>

        <div class="md:col-span-3">
            <h5 class="text-label-md text-secondary-fixed mb-4">Layanan</h5>
            <ul class="space-y-2.5 text-[13.5px] opacity-90">
                <li><a href="{{ route('checkup.show') }}" class="hover:text-secondary-fixed-dim transition-all">Health Check Up</a></li>
                <li><a href="{{ route('company.paket') }}" class="hover:text-secondary-fixed-dim transition-all">Tarif Konsultasi</a></li>
                <li><a href="{{ route('company.pertemuan') }}" class="hover:text-secondary-fixed-dim transition-all">Konsultasi</a></li>
                <li><a href="{{ route('company.bundle.education') }}" class="hover:text-secondary-fixed-dim transition-all">Education Platform</a></li>
                <li><a href="{{ route('company.bundle.recovery') }}" class="hover:text-secondary-fixed-dim transition-all">Recovery Program</a></li>
            </ul>
        </div>

        <div class="md:col-span-2">
            <h5 class="text-label-md text-secondary-fixed mb-4">Perusahaan</h5>
            <ul class="space-y-2.5 text-[13.5px] opacity-90">
                <li><a href="{{ route('company.tentang') }}" class="hover:text-secondary-fixed-dim transition-all">Tentang YFD</a></li>
                <li><a href="{{ route('company.penasihat') }}" class="hover:text-secondary-fixed-dim transition-all">Tim Dokter</a></li>
                <li><a href="{{ route('company.wealthpedia') }}" class="hover:text-secondary-fixed-dim transition-all">Wealthpedia</a></li>
                <li><a href="{{ route('company.informasi') }}" class="hover:text-secondary-fixed-dim transition-all">Informasi</a></li>
            </ul>
        </div>

        <div class="md:col-span-2">
            <h5 class="text-label-md text-secondary-fixed mb-4">Kontak</h5>
            <ul class="space-y-2.5 text-[13.5px] opacity-90">
                <li><a href="mailto:{{ $yfd['email'] }}" class="hover:text-secondary-fixed-dim transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">mail</span>
                    <span class="break-all">{{ $yfd['email'] }}</span>
                </a></li>
                <li><a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="hover:text-secondary-fixed-dim transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">phone_in_talk</span> {{ $yfd['phone'] }}
                </a></li>
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-[16px] mt-0.5">location_on</span>
                    <span>{{ $yfd['address'] }}</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="relative border-t border-on-primary/10">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-5 flex flex-col sm:flex-row justify-between items-center gap-3 text-caption opacity-70">
            <span>© {{ date('Y') }} {{ $yfd['brand'] }} Indonesia. Founded by dr. Ayuti Bulaan QWP &amp; dr. Catherine QWP.</span>
            <div class="flex gap-5">
                <a href="#" class="hover:text-secondary-fixed-dim">Kebijakan Privasi</a>
                <a href="#" class="hover:text-secondary-fixed-dim">Syarat &amp; Ketentuan</a>
            </div>
        </div>
    </div>
</footer>

{{-- ============== Floating WhatsApp CTA ============== --}}
<a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
   class="fixed bottom-6 right-6 md:bottom-8 md:right-8 btn btn-wa btn-lg shadow-lift z-40">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">chat</span>
    <span class="hidden sm:inline">Chat WhatsApp</span>
</a>

<script>
    (function () {
        // ===== Mobile menu toggle =====
        var btn  = document.getElementById('mobileMenuBtn');
        var menu = document.getElementById('mobileMenu');
        if (btn && menu) {
            btn.addEventListener('click', function () { menu.classList.toggle('hidden'); });
            // Close after tapping a real nav link (not <summary>)
            menu.querySelectorAll('a').forEach(function (a) {
                a.addEventListener('click', function () { menu.classList.add('hidden'); });
            });
        }

        // ===== Desktop mega-menu (hover + click) =====
        var openMenus = []; // track all menus for "close others" behavior
        document.querySelectorAll('[data-megamenu]').forEach(function (wrapper) {
            var trigger = wrapper.querySelector('[data-megamenu-trigger]');
            var panel   = wrapper.querySelector('[data-megamenu-panel]');
            var chevron = wrapper.querySelector('[data-megamenu-chevron]');
            if (!trigger || !panel) return;

            var closeTimer = null;

            var open = function () {
                if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
                // Tutup panel lain dulu
                openMenus.forEach(function (m) { if (m !== api) m.close(true); });
                panel.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
            };
            var close = function (immediate) {
                if (closeTimer) clearTimeout(closeTimer);
                var doClose = function () {
                    panel.classList.add('hidden');
                    trigger.setAttribute('aria-expanded', 'false');
                    if (chevron) chevron.style.transform = '';
                    closeTimer = null;
                };
                if (immediate) { doClose(); }
                else { closeTimer = setTimeout(doClose, 180); }   // 180ms grace
            };

            var api = { open: open, close: close };
            openMenus.push(api);

            // Hover di seluruh wrapper (trigger + panel) — pt-3 di panel jadi bridge
            wrapper.addEventListener('mouseenter', open);
            wrapper.addEventListener('mouseleave', function () { close(false); });

            // Kalau cursor masuk ke panel → batalkan close timer
            panel.addEventListener('mouseenter', function () {
                if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            });

            // Click trigger → toggle (keyboard / touch desktop)
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                if (panel.classList.contains('hidden')) open(); else close(true);
            });
            // Klik link di dalam panel → pastikan navigasi jalan (jangan ditangkap trigger/outside)
            panel.querySelectorAll('a[href]').forEach(function (link) {
                var href = link.getAttribute('href');
                if (!href || href === '#') return;
                link.addEventListener('click', function (e) {
                    e.stopPropagation();
                    close(true);
                    // Biarkan browser ikuti href; fallback jika event dibatalkan di tempat lain
                    if (e.defaultPrevented) {
                        window.location.href = href;
                    }
                });
            });

            // Click di luar wrapper → close
            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) close(true);
            });
            // Esc
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') close(true);
            });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
