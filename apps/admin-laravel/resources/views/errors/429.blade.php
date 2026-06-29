@extends('errors.layout')

@section('title', 'Terlalu banyak permintaan')
@section('code', '429')
@section('heading', 'Terlalu banyak permintaan')
@section('message', 'Anda mengirim permintaan terlalu cepat. Tunggu sebentar lalu coba lagi.')

@section('actions')
    <button type="button" onclick="location.reload()"
            class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-3 text-sm font-semibold text-navy-900 hover:bg-gold-400 transition">
        <span class="material-symbols-outlined text-[18px]">refresh</span>
        Coba lagi
    </button>
@endsection
