@extends('Companyprofile.layouts.main')

@section('title', 'Hasil Check-Up — Your Financial Doctor')
@section('description', 'Hasil Financial Health Check-Up Anda — tahap keuangan dan skor.')

@section('content')
<section class="bg-surface-container-lowest py-12 md:py-16">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <span class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 px-4 py-1.5 rounded-full text-label-md font-semibold">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    Check-Up Selesai
                </span>
                <h1 class="font-display text-3xl md:text-4xl font-extrabold text-primary mt-4">
                    Hasil Financial Health Check-Up
                </h1>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <div class="text-5xl mb-3">{{ $stageMeta['emoji'] ?? '' }}</div>
                <div class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ $stageMeta['phase'] ?? '' }}</div>
                <div class="text-3xl font-extrabold text-primary mt-2">{{ $baseline->stage_label }}</div>
                <div class="text-lg font-semibold text-secondary-fixed mt-3">
                    Skor {{ $baseline->financial_stage_score }}/39
                </div>
                <p class="text-sm text-slate-600 mt-4 leading-relaxed max-w-md mx-auto">
                    {{ $stageMeta['diagnosis'] ?? '' }}
                </p>
            </div>

            <div class="mt-6 rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 text-sm text-sky-900">
                Hasil disimpan untuk <strong>{{ $baseline->email }}</strong>.
                @if($fromPortal)
                    Dashboard Anda sudah bisa dipakai — lanjutkan ke menu transaksi dan pantau kesehatan finansial.
                @else
                    Saat Anda membeli YFD First Aid / Bot, gunakan email yang sama — dashboard akan langsung mengenali hasil check-up ini.
                @endif
            </div>

            <div class="flex flex-wrap gap-3 mt-8 justify-center">
                @if($fromPortal)
                    <a href="{{ route('portal.dashboard') }}" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('company.produk') }}" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">send</span>
                        Lihat YFD Bot Telegram
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
