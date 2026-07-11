@php
    $active = $active ?? 'dashboard';
    $displayName = session(\App\Support\PortalSession::DISPLAY_NAME, 'Pengguna');
    $summary = $summary ?? [];
    $assessment = $assessment ?? [];
    $currentMonth = $summary['month'] ?? $assessment['month'] ?? now()->format('Y-m');
    $currentPeriod = $currentPeriod ?? $summary['period_months'] ?? $assessment['period_months'] ?? 1;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'YFD Your Financial Doctor')</title>
    <link rel="icon" type="image/png" href="{{ asset($yfd['logo'] ?? 'images/yfd-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        @include('partials.yfd-tailwind-colors')
                    },
                    fontFamily: { sans: ['Manrope', 'system-ui', 'sans-serif'] },
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <style>
        body { font-family: Manrope, system-ui, sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        .nav-active { background: rgba(255,255,255,.14); box-shadow: inset 3px 0 0 {{ config('yfd_brand.gold') }}; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">
<div class="flex min-h-screen">
    {{-- Sidebar desktop --}}
    <aside class="hidden lg:flex w-72 bg-navy-800 text-white flex-col shrink-0">
        @include('portal.partials.sidebar-nav')
    </aside>

    {{-- Mobile drawer --}}
    <div id="mobileDrawer" class="fixed inset-0 z-50 hidden lg:hidden">
        <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('mobileDrawer').classList.add('hidden')"></div>
        <aside class="relative w-72 max-w-[85vw] h-full bg-navy-800 text-white flex flex-col">
            @include('portal.partials.sidebar-nav')
        </aside>
    </div>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" class="lg:hidden p-2 rounded-lg border border-slate-200 shrink-0"
                            onclick="document.getElementById('mobileDrawer').classList.remove('hidden')">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <img src="{{ asset($yfd['logo'] ?? 'images/yfd-logo.png') }}" alt="{{ $yfd['short'] ?? 'YFD' }}"
                         class="h-9 w-auto rounded-lg bg-white border border-slate-200 px-1.5 py-1 shrink-0 lg:hidden">
                    <div class="min-w-0">
                        <div class="text-[10px] uppercase tracking-[0.16em] text-gold-600 font-bold lg:hidden">Your Financial Doctor</div>
                        <div class="text-xs text-slate-500 truncate">Halo, {{ $displayName }}</div>
                        <h1 class="text-lg sm:text-xl font-bold text-navy-800 truncate">@yield('heading')</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    @if(!($isFtsaOnlyPortalUser ?? false))
                    <form method="get" class="flex items-center gap-2 flex-wrap">
                        <span class="material-symbols-outlined text-slate-400 text-xl hidden sm:inline">calendar_month</span>
                        <select name="month" onchange="this.form.submit()"
                                class="rounded-lg border-slate-300 text-sm py-2 pl-2 pr-8 bg-white">
                            @foreach(($months ?? []) as $m)
                                <option value="{{ $m['value'] }}" @selected($m['value'] === $currentMonth)>{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                        @if(!empty($periods))
                            <select name="period" onchange="this.form.submit()"
                                    class="rounded-lg border-slate-300 text-sm py-2 pl-2 pr-8 bg-white">
                                @foreach($periods as $p)
                                    <option value="{{ $p['value'] }}" @selected((int) $p['value'] === (int) $currentPeriod)>{{ $p['label'] }}</option>
                                @endforeach
                            </select>
                        @endif
                    </form>
                    @endif
                    <form method="post" action="{{ route('portal.logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-red-600 px-2 py-2">
                            <span class="material-symbols-outlined text-lg">logout</span>
                            <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        @if(session('error'))
            <div class="mx-4 sm:mx-6 mt-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="mx-4 sm:mx-6 mt-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">
                {{ session('warning') }}
            </div>
        @endif
        @if(session('info'))
            <div class="mx-4 sm:mx-6 mt-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-900 px-4 py-3 text-sm">
                {{ session('info') }}
            </div>
        @endif
        @if(session('success'))
            <div class="mx-4 sm:mx-6 mt-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-4 sm:p-6 space-y-6">
            @yield('content')
        </div>
    </main>
</div>
<script>
(function () {
    var autoUrl = @json(route('portal.timezone.auto'));
    var token = @json(csrf_token());
    try {
        var browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (!browserTz) return;
        fetch(autoUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ timezone: browserTz }),
            credentials: 'same-origin',
        }).then(function (r) { return r.ok ? r.json() : null; })
          .then(function (data) {
              if (!data || !data.ok) return;
              var badge = document.getElementById('portal-tz-badge');
              if (badge && data.label) badge.textContent = data.label;
          }).catch(function () {});
    } catch (e) {}
})();
</script>
@stack('scripts')
</body>
</html>
