@extends('errors.layout')

@section('title', 'Sedang pemeliharaan')
@section('code', '503')
@section('heading', 'Situs sedang pemeliharaan')
@section('message', 'Kami sedang melakukan perbaikan singkat agar layanan lebih baik. Mohon kembali lagi sebentar lagi.')

@section('actions')
    <button type="button" onclick="location.reload()"
            class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-3 text-sm font-semibold text-navy-900 hover:bg-gold-400 transition">
        <span class="material-symbols-outlined text-[18px]">refresh</span>
        Coba lagi
    </button>
@endsection
