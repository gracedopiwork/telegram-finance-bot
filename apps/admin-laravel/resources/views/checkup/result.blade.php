@extends('Companyprofile.layouts.main')

@section('title', 'Hasil Check-Up — Your Financial Doctor')
@section('description', 'Hasil Financial Health Check-Up Anda — tahap keuangan dan skor.')

@section('content')
@php
    use App\Support\ConsultationPricing;
    // Sama dengan blok form FHCU (checkup/form.blade.php .checkup-wizard) untuk semua tahap:
    // mint transparan di atas bg-background. Warna panel per tahap di admin tidak dipakai di sini.
    $panelTint = config('yfd_brand.mint_light').'33';
    $logo = asset($yfd['logo'] ?? 'images/yfd-logo.png');
@endphp

<section class="bg-surface-container-lowest py-10 md:py-16">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-5xl mx-auto">
            {{-- Kartu hasil ala mockup klien --}}
            <div class="rounded-3xl overflow-hidden shadow-2xl border border-black/5 bg-white">
                <div class="grid md:grid-cols-2 min-h-[420px]">
                    {{-- Panel kiri: teks + skor --}}
                    <div class="p-8 sm:p-10 flex flex-col justify-center text-slate-900 bg-background" style="background-image: linear-gradient({{ $panelTint }}, {{ $panelTint }})">
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
                Hasil disimpan untuk <strong>{{ $baseline->email }}</strong>
                dan salinan dikirim ke email tersebut (hanya hasil Anda).
                Evaluasi ulang tersedia mulai <strong>{{ $baseline->formatNextReview('d M Y') }}</strong> (setiap 3 bulan).
                Data di dashboard tidak dihapus.
                @if($fromPortal)
                    @if($isFtsaOnlyPortal ?? false)
                        Data check-up terhubung ke akun FTSA Anda. Lanjutkan ke kuesioner FTSA 1–32 jika belum diisi.
                    @else
                        Dashboard Anda sudah bisa dipakai — lanjutkan ke menu transaksi dan pantau kesehatan finansial.
                    @endif
                @else
                    Saat booking konsultasi, gunakan email yang sama — hasil check-up ini langsung dikenali.
                @endif
            </div>

            <div class="mt-6 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="font-headline-md text-headline-md text-primary mb-2">Langkah berikutnya</h2>
                        <p class="text-sm text-on-surface-variant mb-3">
                            Hasil check-up Anda: tahap <strong>{{ $consultationStagePrice['label'] ?? ($stageDisplay['label'] ?? $baseline->stage_label) }}</strong>.
                            Bahas hasilnya bersama Financial Planner di sesi konsultasi 1-on-1.
                        </p>
                        @if($consultationStagePrice)
                            <p class="text-xs text-on-surface-variant max-w-lg">
                                Tarif konsultasi 1-on-1 untuk tahap {{ $consultationStagePrice['label'] }}:
                                <strong>{{ ConsultationPricing::formatRupiah((int) $consultationStagePrice['amount']) }}</strong>/jam.
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2 shrink-0">
                        <a href="{{ $continueBookingUrl ?? route('company.konsultasi') }}" class="btn btn-gold btn-lg text-center">
                            <span class="material-symbols-outlined text-[20px]">event_available</span>
                            Konsultasi dengan Financial Planner
                        </a>
                    </div>
                </div>
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
                        <a href="{{ route('portal.ftsa.create') }}" class="btn btn-ghost btn-lg">
                            <span class="material-symbols-outlined text-[20px]">psychology</span>
                            Lengkapi FTSA 1–32
                        </a>
                    @endif
                @elseif($fromPortal)
                    <a href="{{ route('portal.dashboard') }}" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>
                        Buka Dashboard
                    </a>
                @endif
                <a href="{{ route('checkup.show') }}" class="btn btn-ghost btn-lg">Check-Up Ulang</a>
            </div>
        </div>
    </div>
</section>
@endsection
