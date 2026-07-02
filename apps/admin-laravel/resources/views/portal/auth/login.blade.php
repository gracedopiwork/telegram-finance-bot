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
                <li class="flex items-center gap-2"><span class="material-symbols-outlined text-gold-400">check_circle</span> Aktivasi bot → masuk dashboard</li>
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
                <p class="text-sm text-slate-600 mt-2">Pilih salah satu cara login:</p>

                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-emerald-600 mt-0.5">send</span>
                        <div>
                            <div class="text-sm font-bold text-navy-800">Opsi 1 — Dari Telegram (disarankan)</div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                Ketik <strong>/web</strong> di bot YFD First Aid Bot → klik link → langsung masuk dashboard.
                                Tanpa isi email atau kode lisensi.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-center gap-3 text-xs text-slate-400 uppercase tracking-wider font-bold">
                        <span class="flex-1 border-t"></span>
                        Opsi 2 — Login manual
                        <span class="flex-1 border-t"></span>
                    </div>
                </div>

                @if($errors->any())
                    <div class="mt-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('portal.login.attempt') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-navy-800 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:ring-navy-500 focus:border-navy-500"
                               placeholder="email@contoh.com">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-navy-800 mb-1.5">Kode Lisensi</label>
                        <input type="text" name="license_key" value="{{ old('license_key') }}" required
                               class="w-full rounded-xl border-slate-300 text-sm py-2.5 uppercase focus:ring-navy-500 focus:border-navy-500"
                               placeholder="YFD-XXXX-XXXX">
                        <p class="text-[11px] text-slate-500 mt-1">Pembeli <strong>FTSA saja</strong> bisa login langsung di sini (tanpa /activate bot). Pembeli bot: aktifkan dulu di Telegram.</p>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-navy-800 text-white font-bold py-3 hover:bg-navy-700 transition-colors">
                        Masuk dengan Email & Lisensi
                    </button>
                </form>
            </div>
            <p class="text-center text-xs text-slate-500 mt-6">
                Belum punya lisensi? <a href="{{ route('company.produk') }}" class="text-navy-800 font-semibold hover:underline">Lihat YFD Bot</a>
            </p>
        </div>
    </div>
</div>
@endsection
