@extends('Companyprofile.layouts.main')

@section('title', 'Kenali Kesehatan Finansialmu — Financial Health Check-Up')
@section('description', 'Kenali kondisi finansialmu saat ini lewat Financial Health Check-Up (FHCU) gratis dari Your Financial Doctor.')

@push('head')
<style>
    .checkup-wizard { background: {{ config('yfd_brand.mint_light') }}33; }
    .checkup-option {
        display: flex; align-items: flex-start; gap: 0.75rem;
        background: rgba(255,255,255,0.35);
        border: 2px solid rgba(13,43,78,0.15);
        border-radius: 0.75rem;
        padding: 0.85rem 1rem;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .checkup-option:has(input:checked) {
        border-color: {{ config('yfd_brand.navy') }};
        background: rgba(255,255,255,0.65);
        box-shadow: 0 0 0 1px {{ config('yfd_brand.navy') }};
    }
    {{-- Radio tetap terlihat: pilihan persetujuan harus jelas belum dipilih. --}}
    .checkup-option input {
        accent-color: {{ config('yfd_brand.navy') }};
        width: 1.125rem; height: 1.125rem; margin-top: 0.15rem; flex-shrink: 0;
    }
    .checkup-ok-btn {
        background: {{ config('yfd_brand.gold') }}; color: {{ config('yfd_brand.navy') }}; font-weight: 800;
        border-radius: 0.5rem; padding: 0.75rem 1.75rem;
        border: none; cursor: pointer;
    }
</style>
@endpush

@section('content')
<section class="py-8 md:py-12">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-2xl mx-auto">
            @if(session('error'))
                <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif

            <div class="checkup-wizard rounded-2xl p-6 sm:p-8 space-y-4 text-slate-800">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Kenali Kesehatan Finansialmu</h1>
                <p>Sebelum bisa lebih sehat secara finansial, kamu perlu tahu dahulu kondisi finansial kamu saat ini.</p>
                <p>Melalui <strong>Financial Health Check-Up (FHCU)</strong> dari Your Financial Doctor (YFD), kamu bisa mengenali kondisi finansialmu saat ini, sebelum melangkah untuk menjadi lebih sehat secara finansial.</p>

                <h2 class="text-lg font-extrabold text-slate-900 pt-2">Privasi Data dan Persetujuan Penelitian</h2>
                <p>Selain memberikan gambaran kesehatan finansial secara personal, YFD ingin menggunakan data FHCU untuk penelitian dan pengembangan wawasan mengenai kesehatan finansial Indonesia.</p>
                <p>Data yang digunakan akan dianalisis secara agregat (data gabungan). Email dan informasi yang secara langsung mengidentifikasi individu tidak akan dicantumkan dalam publikasi hasil penelitian.</p>
                <p>Baca selengkapnya di <a href="{{ route('company.privacy') }}" class="underline font-semibold">Kebijakan Privasi</a>.</p>

                <form method="post" action="{{ route('checkup.start') }}" id="researchConsentForm" class="space-y-4 pt-2">
                    @csrf
                    <fieldset class="space-y-3">
                        <legend class="font-bold text-slate-900 mb-3">Apakah kamu bersedia data FHCU-mu digunakan untuk penelitian YFD?</legend>
                        <label class="checkup-option">
                            <input type="radio" name="research_consent" value="1" required>
                            <span><strong>Saya bersedia</strong> data saya digunakan untuk penelitian dalam bentuk agregat.</span>
                        </label>
                        <label class="checkup-option">
                            <input type="radio" name="research_consent" value="0" required>
                            <span><strong>Saya tidak bersedia</strong> data saya digunakan untuk penelitian.</span>
                        </label>
                        @error('research_consent')
                            <p class="text-rose-700 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <p class="text-sm">Persetujuan ini bersifat sukarela. Apa pun pilihanmu, kamu tetap dapat menyelesaikan FHCU dan menerima hasil personalmu.</p>

                    <button type="submit" class="checkup-ok-btn w-full sm:w-auto">Mulai Financial Health Check-Up</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
