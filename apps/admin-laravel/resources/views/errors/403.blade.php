@extends('errors.layout')

@section('title', 'Akses ditolak')
@section('code', '403')
@section('heading', 'Akses ditolak')
@section('message', 'Anda tidak memiliki izin untuk membuka halaman ini. Jika ini seharusnya bisa diakses, silakan login atau hubungi administrator.')

@section('actions')
    <a href="{{ url('/') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-3 text-sm font-semibold text-navy-900 hover:bg-gold-400 transition">
        <span class="material-symbols-outlined text-[18px]">home</span>
        Ke beranda
    </a>
    @if (Route::has('login'))
        <a href="{{ route('portal.login') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10 transition">
            <span class="material-symbols-outlined text-[18px]">login</span>
            Login
        </a>
    @endif
@endsection
