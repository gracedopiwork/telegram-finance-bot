@extends('errors.layout')

@section('title', 'Halaman tidak ditemukan')
@section('code', '404')
@section('heading', 'Halaman tidak ditemukan')
@section('message', 'Link yang Anda buka tidak ada atau sudah dipindahkan. Coba kembali ke beranda atau gunakan menu navigasi.')

@section('actions')
    <a href="{{ url('/') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-3 text-sm font-semibold text-navy-900 hover:bg-gold-400 transition">
        <span class="material-symbols-outlined text-[18px]">home</span>
        Ke beranda
    </a>
    <button type="button" onclick="history.back()"
            class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Kembali
    </button>
@endsection
