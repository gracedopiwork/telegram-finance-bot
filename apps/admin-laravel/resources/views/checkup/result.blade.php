@extends('Companyprofile.layouts.main')

@section('title', 'Hasil Check-Up — Your Financial Doctor')
@section('description', 'Hasil Financial Health Check-Up Anda — tahap keuangan dan skor.')

@section('content')
@php
    $panelColor = $stageDisplay['panel_color'] ?? '#7EC8C8';
    $logo = asset($yfd['logo'] ?? 'images/yfd-logo.png');
@endphp

<section class="bg-surface-container-lowest py-10 md:py-16">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-5xl mx-auto">
            {{-- Kartu hasil ala mockup klien --}}
            <div class="rounded-3xl overflow-hidden shadow-2xl border border-black/5 bg-white">
                <div class="grid md:grid-cols-2 min-h-[420px]">
                    {{-- Panel kiri: teks + skor --}}
                    <div class="p-8 sm:p-10 flex flex-col justify-center text-slate-900" style="background-color: {{ $panelColor }}">
                        <div class="flex items-center gap-3 mb-8">
                            <img src="{{ $logo }}" alt="YFD" class="h-10 w-auto bg-white/90 rounded-lg px-2 py-1">
                            <span class="font-extrabold text-lg tracking-tight">{{ $yfd['short'] ?? 'YFD' }}</span>
                        </div>

                        <div class="text-sm font-bold uppercase tracking-[0.2em] text-slate-800/70 mb-2">
                            {{ $stageDisplay['phase'] ?? '' }}
                        </div>
                        <h1 class="font-display text-4xl sm:text-5xl font-extrabold leading-tight text-slate-900">
                            {{ $stageDisplay['label'] ?? $baseline->stage_label }}
                        </h1>

                        <div class="inline-flex items-center gap-2 mt-5 bg-white/80 backdrop-blur rounded-full px-4 py-2 w-fit shadow-sm">
                            <span class="material-symbols-outlined text-primary text-xl">score</span>
                            <span class="font-bold text-lg">Skor {{ $baseline->financial_stage_score }}/39</span>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-900/10">
                            <div class="flex items-start gap-2 text-slate-900">
                                <span class="material-symbols-outlined text-xl mt-0.5">health_and_safety</span>
                                <div>
                                    <div class="font-bold">{{ $stageDisplay['risk_label'] ?? 'Risiko keuangan' }}:</div>
                                    <p class="text-sm sm:text-base leading-relaxed mt-1 text-slate-800/90">
                                        {{ $stageDisplay['risk_description'] ?? ($stageDisplay['diagnosis'] ?? '') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Panel kanan: ilustrasi --}}
                    <div class="relative bg-gradient-to-br from-slate-50 to-white flex items-center justify-center p-6 sm:p-10 overflow-hidden">
                        @if(!empty($stageDisplay['illustration_url']))
                            <img src="{{ $stageDisplay['illustration_url'] }}" alt="{{ $stageDisplay['label'] }}"
                                 class="max-h-[360px] w-full object-contain">
                        @else
                            <div class="text-center">
                                <div class="text-7xl mb-4">{{ $stageDisplay['emoji'] ?? '💰' }}</div>
                                <div class="font-display text-4xl font-extrabold text-secondary-fixed drop-shadow-sm">
                                    {{ $stageDisplay['label'] ?? $baseline->stage_label }}
                                </div>
                                <p class="text-sm text-slate-500 mt-3 max-w-xs mx-auto">
                                    {{ $stageDisplay['diagnosis'] ?? '' }}
                                </p>
                            </div>
                        @endif
                        <div class="absolute top-4 right-4 bg-secondary-fixed text-on-secondary-container text-xs font-bold px-3 py-1 rounded-full shadow">
                            {{ $stageDisplay['label'] ?? '' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-900">
                Hasil disimpan untuk <strong>{{ $baseline->email }}</strong>.
                @if($fromPortal)
                    @if($isFtsaOnlyPortal ?? false)
                        Data diagnostik terhubung ke dashboard FTSA Anda. Lanjutkan ke FTSA 1–32 jika belum diisi.
                    @else
                        Dashboard Anda sudah bisa dipakai — lanjutkan ke menu transaksi dan pantau kesehatan finansial.
                    @endif
                @else
                    Saat Anda membeli YFD First Aid / Bot, gunakan email yang sama — dashboard akan langsung mengenali hasil check-up ini.
                @endif
            </div>

            <div class="flex flex-wrap gap-3 mt-8 justify-center">
                @if($fromPortal && !empty($portalNextUrl))
                    <a href="{{ $portalNextUrl }}" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>
                        {{ $portalNextLabel ?? 'Lanjutkan' }}
                    </a>
                @elseif($fromPortal && !empty($portalHomeRoute))
                    <a href="{{ route($portalHomeRoute) }}" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>
                        {{ ($isFtsaOnlyPortal ?? false) ? 'Buka Dashboard FTSA' : 'Buka Dashboard' }}
                    </a>
                    @if($isFtsaOnlyPortal ?? false)
                        <a href="{{ route('portal.baseline.create') }}" class="btn btn-ghost btn-lg">
                            <span class="material-symbols-outlined text-[20px]">psychology</span>
                            Lengkapi FTSA 1–32
                        </a>
                    @endif
                @elseif($fromPortal)
                    <a href="{{ route('portal.dashboard') }}" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('company.produk') }}" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">send</span>
                        Lihat YFD First Aid
                    </a>
                    <a href="{{ route('company.paket') }}" class="btn btn-ghost btn-lg">
                        Lihat Paket Layanan
                    </a>
                @endif
                <a href="{{ route('checkup.show') }}" class="btn btn-ghost btn-lg">Check-Up Ulang</a>
            </div>
        </div>
    </div>
</section>
@endsection
