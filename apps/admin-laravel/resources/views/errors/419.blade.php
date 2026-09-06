@extends('errors.layout')

@section('title', 'Sesi berakhir')
@section('code', '419')
@section('heading', 'Sesi Anda berakhir')
@section('message', 'Halaman ini sudah kedaluwarsa karena tidak ada aktivitas terlalu lama. Muat ulang halaman lalu coba lagi.')

@section('actions')
    @php
        // Jangan location.reload() — setelah POST gagal (CSRF), reload sering re-POST dan tetap 419.
        $retryUrl = url()->previous();
        if ($retryUrl === '' || $retryUrl === url()->current()) {
            $retryUrl = str_contains((string) request()->path(), 'portal')
                ? route('portal.login')
                : url('/');
        }
    @endphp
    <a href="{{ $retryUrl }}"
       class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-3 text-sm font-semibold text-navy-900 hover:bg-gold-400 transition">
        <span class="material-symbols-outlined text-[18px]">refresh</span>
        Muat ulang halaman
    </a>
    <a href="{{ route('portal.login') }}"
       class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
        <span class="material-symbols-outlined text-[18px]">login</span>
        Login portal
    </a>
    <a href="{{ url('/') }}"
       class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
        <span class="material-symbols-outlined text-[18px]">home</span>
        Ke beranda
    </a>
@endsection
