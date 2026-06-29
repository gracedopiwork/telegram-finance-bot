@extends('errors.layout')

@section('title', 'Terjadi kesalahan')
@section('code', '500')
@section('heading', 'Maaf, ada gangguan sementara')
@section('message', 'Tim kami sedang menangani masalah ini. Silakan coba lagi dalam beberapa menit. Jika berlanjut, hubungi kami lewat halaman Informasi.')

@section('actions')
    <a href="{{ url('/') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-3 text-sm font-semibold text-navy-900 hover:bg-gold-400 transition">
        <span class="material-symbols-outlined text-[18px]">home</span>
        Ke beranda
    </a>
    <button type="button" onclick="location.reload()"
            class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
        <span class="material-symbols-outlined text-[18px]">refresh</span>
        Muat ulang
    </button>
@endsection
