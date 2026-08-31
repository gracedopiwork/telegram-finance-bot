@extends('portal.layouts.guest')

@section('title', 'Login Portal — YFD Your Financial Doctor')

@section('content')
@php $logoUrl = asset($yfd['logo'] ?? 'images/yfd-logo.png'); @endphp
<div class="min-h-screen flex">
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-navy-800 via-navy-700 to-navy-800 text-white p-12 flex-col justify-between">
        <div>
            <div class="flex items-center gap-3">
                <img src="{{ $logoUrl }}" alt="{{ $yfd['short'] ?? 'YFD' }}" class="h-12 w-auto rounded-xl bg-white/95 px-2 py-1">
                <div>
                    <div class="text-xs uppercase tracking-widest text-gold-400 font-bold">Your Financial Doctor</div>
                    <div class="text-xl font-extrabold">YFD First Aid Dashboard</div>
                </div>
            </div>
            <p class="mt-10 text-lg text-white/85 leading-relaxed max-w-md">
                Catat lewat Telegram, pantau cashflow, bucket budget, dan skor impulsifitas — semua di satu tempat.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-white/75">
                <li class="flex items-center gap-2"><span class="material-symbols-outlined text-gold-400">check_circle</span> Aktivasi YFD First Aid → masuk dashboard</li>
                <li class="flex items-center gap-2"><span class="material-symbols-outlined text-gold-400">check_circle</span> Isi Baseline Data (diagnostik wajib)</li>
                <li class="flex items-center gap-2"><span class="material-symbols-outlined text-gold-400">check_circle</span> Catat transaksi & pantau dashboard</li>
            </ul>
        </div>
        <p class="text-sm text-white/50 italic">"Kesehatan finansial dimulai dari kesadaran hari ini."</p>
    </div>

    <div class="flex-1 flex items-center justify-center p-6 sm:p-12 bg-slate-50">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <img src="{{ $logoUrl }}" alt="{{ $yfd['short'] ?? 'YFD' }}" class="h-10 w-auto rounded-lg bg-white px-1.5 py-1">
                <div class="font-bold text-navy-800">Your Financial Doctor</div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-200/80 p-8">
                <h2 class="text-2xl font-extrabold text-navy-800">Masuk Portal</h2>
                <p class="text-sm text-slate-600 mt-2">Setelah punya password, login cukup <strong>email + password</strong>. Kode lisensi hanya untuk pertama kali.</p>

                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-emerald-600 mt-0.5">send</span>
                        <div>
                            <div class="text-sm font-bold text-navy-800">Opsi cepat — Dari Telegram</div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Ketik <strong>/web</strong> di <strong>YFD First Aid</strong> → klik link → masuk dashboard.
                                Jika belum punya password, sistem akan minta membuatnya dulu.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-center gap-3 text-xs text-slate-400 uppercase tracking-wider font-bold">
                        <span class="flex-1 border-t"></span>
                        Atau login manual
                        <span class="flex-1 border-t"></span>
                    </div>
                </div>

                @if($errors->any())
                    <div class="mt-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('portal.login.attempt') }}" class="mt-4 space-y-4" id="portalLoginForm">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-navy-800 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:ring-navy-500 focus:border-navy-500"
                               placeholder="email@contoh.com">
                    </div>

                    @php $loginMethod = old('login_method', 'password'); @endphp
                    <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
                        <label class="cursor-pointer">
                            <input type="radio" name="login_method" value="password" class="peer sr-only" {{ $loginMethod !== 'license' ? 'checked' : '' }}>
                            <span class="block text-center text-sm font-semibold rounded-lg py-2 peer-checked:bg-white peer-checked:shadow peer-checked:text-navy-800 text-slate-500">Password</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="login_method" value="license" class="peer sr-only" {{ $loginMethod === 'license' ? 'checked' : '' }}>
                            <span class="block text-center text-sm font-semibold rounded-lg py-2 peer-checked:bg-white peer-checked:shadow peer-checked:text-navy-800 text-slate-500">Pertama kali</span>
                        </label>
                    </div>

                    <div id="loginPasswordField">
                        <label class="block text-sm font-semibold text-navy-800 mb-1.5">Password</label>
                        <input type="password" name="password"
                               class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:ring-navy-500 focus:border-navy-500"
                               placeholder="Password portal" autocomplete="current-password">
                        <p class="text-[11px] text-slate-500 mt-1">
                            Belum punya password? Pilih <strong>Pertama kali</strong> dan masuk dengan kode lisensi, lalu buat password di dashboard.
                        </p>
                    </div>
                    <div id="loginLicenseField" class="hidden">
                        <label class="block text-sm font-semibold text-navy-800 mb-1.5">Kode Lisensi (password awal)</label>
                        <input type="text" name="license_key" value="{{ old('license_key') }}"
                               class="w-full rounded-xl border-slate-300 text-sm py-2.5 uppercase focus:ring-navy-500 focus:border-navy-500"
                               placeholder="YFD-XXXX-XXXX" autocomplete="off">
                        <p class="text-[11px] text-slate-500 mt-1">
                            Hanya untuk login pertama. Setelah masuk, Anda wajib buat password sendiri. Kode lisensi tetap dipakai untuk <code class="text-[10px]">/activate</code> di Telegram.
                        </p>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-navy-800 text-white font-bold py-3 hover:bg-navy-700 transition-colors">
                        Masuk
                    </button>
                </form>
                <script>
                    (function () {
                        const form = document.getElementById('portalLoginForm');
                        if (!form) return;
                        const licenseBox = document.getElementById('loginLicenseField');
                        const passwordBox = document.getElementById('loginPasswordField');
                        const licenseInput = form.querySelector('input[name="license_key"]');
                        const passwordInput = form.querySelector('input[name="password"]');
                        function sync() {
                            const method = (form.querySelector('input[name="login_method"]:checked') || {}).value || 'password';
                            const usePassword = method === 'password';
                            licenseBox.classList.toggle('hidden', usePassword);
                            passwordBox.classList.toggle('hidden', !usePassword);
                            if (licenseInput) licenseInput.required = !usePassword;
                            if (passwordInput) passwordInput.required = usePassword;
                        }
                        form.querySelectorAll('input[name="login_method"]').forEach(function (el) {
                            el.addEventListener('change', sync);
                        });
                        sync();
                    })();
                </script>
            </div>
            <p class="text-center text-xs text-slate-500 mt-6">
                Belum punya lisensi? <a href="{{ route('company.produk') }}" class="text-navy-800 font-semibold hover:underline">Lihat YFD First Aid</a>
            </p>
        </div>
    </div>
</div>
@endsection
