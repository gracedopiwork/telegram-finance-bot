@extends('errors.layout')

@section('title', 'Sesi berakhir')
@section('code', '419')
@section('heading', 'Sesi Anda berakhir')
@section('message', 'Halaman ini sudah kedaluwarsa karena tidak ada aktivitas terlalu lama. Muat ulang halaman lalu coba lagi.')

@section('actions')
    <button type="button" onclick="location.reload()"
            class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-3 text-sm font-semibold text-navy-900 hover:bg-gold-400 transition">
        <span class="material-symbols-outlined text-[18px]">refresh</span>
        Muat ulang
    </button>
    <a href="{{ url('/') }}"
       class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
        <span class="material-symbols-outlined text-[18px]">home</span>
        Ke beranda
    </a>
@endsection
