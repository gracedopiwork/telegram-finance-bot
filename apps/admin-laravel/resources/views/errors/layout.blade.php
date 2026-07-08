<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Terjadi Kesalahan') — Your Financial Doctor</title>
    <link rel="icon" type="image/png" href="{{ asset('images/yfd-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        @include('partials.yfd-tailwind-colors')
                    },
                    fontFamily: { sans: ['Manrope', 'system-ui', 'sans-serif'] },
                },
            },
        };
    </script>
    <style>
        body { font-family: Manrope, system-ui, sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-navy-900 via-navy-800 to-navy-700 text-white antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <a href="{{ url('/') }}" class="mb-10 flex items-center gap-3 opacity-90 hover:opacity-100 transition">
            <img src="{{ asset('images/yfd-logo.png') }}" alt="YFD" class="h-10 w-10 rounded-full bg-white/10 p-1">
            <span class="text-sm font-semibold tracking-wide text-white/90">Your Financial Doctor</span>
        </a>

        <main class="w-full max-w-lg text-center">
            @hasSection('code')
                <p class="text-7xl font-extrabold text-gold-400 leading-none mb-4">@yield('code')</p>
            @endif

            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-3">@yield('heading')</h1>
            <p class="text-base text-white/75 leading-relaxed mb-8">@yield('message')</p>

            <div class="flex flex-wrap gap-3 justify-center">
                @yield('actions')
            </div>
        </main>

        <p class="mt-12 text-xs text-white/40">&copy; {{ date('Y') }} Your Financial Doctor</p>
    </div>
</body>
</html>
