@extends('Companyprofile.layouts.main')

@section('title', 'Financial Health Check-Up — Your Financial Doctor')
@section('description', 'Cek tahap kesehatan finansial Anda secara gratis. Tanpa login — cukup email untuk menyimpan hasil.')

@section('content')
<section class="bg-surface-container-lowest py-12 md:py-16">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <span class="inline-flex items-center gap-2 bg-secondary-container text-on-secondary-container px-4 py-1.5 rounded-full text-label-md font-semibold">
                    <span class="material-symbols-outlined text-[18px]">monitor_heart</span>
                    Gratis · Tanpa Login
                </span>
                <h1 class="font-display text-3xl md:text-4xl font-extrabold text-primary mt-4">
                    Financial Health Check-Up
                </h1>
                <p class="text-body-md text-on-surface-variant mt-3 max-w-2xl mx-auto">
                    Ketahui tahap keuangan Anda saat ini. Hasil disimpan dengan email Anda —
                    saat membeli YFD First Aid nanti, dashboard langsung aktif tanpa isi ulang.
                </p>
            </div>

            @if(session('error'))
                <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">
                    {{ session('warning') }}
                </div>
            @endif

            <form method="post" action="{{ route('checkup.store') }}" class="space-y-6">
                @csrf

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <label class="block text-sm font-semibold text-slate-800 mb-2" for="checkup-email">
                        Email Anda
                    </label>
                    <input type="email" id="checkup-email" name="email" required
                           value="{{ old('email', $prefillEmail ?? '') }}"
                           class="w-full rounded-lg border-slate-300 text-sm"
                           placeholder="nama@email.com">
                    <p class="text-xs text-slate-500 mt-2">
                        Gunakan email yang sama saat checkout agar hasil check-up otomatis terhubung ke akun Anda.
                    </p>
                    @error('email')
                        <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @include('checkup.partials.financial-stage-fields', ['financialStage' => $financialStage])

                <div class="flex flex-wrap gap-3 pb-4">
                    <button type="submit" class="btn btn-gold btn-lg">
                        <span class="material-symbols-outlined text-[20px]">fact_check</span>
                        Lihat Hasil Check-Up
                    </button>
                    <a href="{{ route('company.home') }}" class="btn btn-ghost btn-lg">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
