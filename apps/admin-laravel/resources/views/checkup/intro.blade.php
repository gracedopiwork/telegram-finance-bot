@extends('Companyprofile.layouts.main')

@section('title', 'Kenali Kesehatan Finansialmu — Financial Health Check-Up')
@section('description', 'Kenali kondisi finansialmu saat ini lewat Financial Health Check-Up (FHCU) gratis dari Your Financial Doctor.')

@push('head')
<style>
    .checkup-wizard { background: {{ config('yfd_brand.mint_light') }}33; }
    .checkup-ok-btn {
        display: inline-block;
        background: {{ config('yfd_brand.gold') }}; color: {{ config('yfd_brand.navy') }}; font-weight: 800;
        border-radius: 0.5rem; padding: 0.75rem 1.75rem;
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
            @include('checkup.partials.booking-banner')

            <div class="checkup-wizard rounded-2xl p-6 sm:p-8 space-y-4 text-slate-800">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Kenali Kesehatan Finansialmu</h1>
                <p>Sebelum bisa lebih sehat secara finansial, kamu perlu tahu dahulu kondisi finansial kamu saat ini.</p>
                <p>Melalui <strong>Financial Health Check-Up (FHCU)</strong> dari Your Financial Doctor (YFD), kamu bisa mengenali kondisi finansialmu saat ini, sebelum melangkah untuk menjadi lebih sehat secara finansial.</p>
                <div class="pt-2">
                    <a href="{{ route('checkup.form') }}" class="checkup-ok-btn w-full sm:w-auto text-center">Mulai Financial Health Check-Up</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
