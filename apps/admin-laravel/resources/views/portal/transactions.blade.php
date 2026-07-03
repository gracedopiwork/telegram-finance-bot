@extends('portal.layouts.app')

@section('title', 'Data Transaksi — YFD')
@section('heading', 'Input Data — Riwayat Transaksi')

@section('content')
@php
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
@endphp

@include('portal.partials.onboarding-banners')

<div id="input-data" class="scroll-mt-24 space-y-6">
    @include('portal.partials.transactions-input-panel', [
        'summary' => $summary,
        'fmt' => $fmt,
        'showBotBanner' => true,
        'dashboardLink' => true,
    ])
</div>
@endsection

@include('portal.partials.transactions-delete-script', ['summary' => $summary])
