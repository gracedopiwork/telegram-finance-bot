@extends('portal.layouts.app')

@section('title', 'Data Transaksi — YFD')
@section('heading', 'Input Data — Riwayat Transaksi')

@section('content')
@php
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
@endphp

<div class="bg-gradient-to-r from-navy-800 to-navy-600 rounded-2xl p-5 sm:p-6 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-3xl text-gold-400">send</span>
        <div>
            <h3 class="font-bold text-lg">Catat via Telegram Bot</h3>
            <p class="text-sm text-white/80 mt-1">Kirim teks atau foto struk. AI akan parse kategori, mood, dan impulsifitas — lalu simpan ke dashboard ini.</p>
        </div>
    </div>
    <div class="text-sm bg-white/10 rounded-xl px-4 py-2 font-mono shrink-0">/catat makan siang 35rb</div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-2xl font-extrabold text-navy-800">{{ $summary['transaction_count'] }}</div>
        <div class="text-xs text-slate-500 mt-1">Total transaksi</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-emerald-700">{{ $fmt($summary['income']) }}</div>
        <div class="text-xs text-slate-500 mt-1">Pemasukan</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-rose-600">{{ $fmt($summary['expense']) }}</div>
        <div class="text-xs text-slate-500 mt-1">Pengeluaran</div>
    </div>
    <div class="bg-white rounded-xl border p-4 text-center">
        <div class="text-lg font-extrabold text-navy-800">{{ $summary['saving_rate'] }}%</div>
        <div class="text-xs text-slate-500 mt-1">Saving rate</div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b flex items-center justify-between">
        <h3 class="font-bold text-navy-800">Tabel Transaksi <span class="text-slate-400 font-normal text-sm">({{ $summary['month_label'] }})</span></h3>
        <a href="{{ route('portal.dashboard', ['month' => $summary['month']]) }}" class="text-sm text-navy-800 font-semibold hover:underline">Lihat Dashboard →</a>
    </div>

    @if(empty($summary['transactions']))
        @include('portal.partials.empty-state')
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Jenis</th>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 font-semibold hidden md:table-cell">Sub</th>
                        <th class="px-4 py-3 font-semibold text-right">Nominal</th>
                        <th class="px-4 py-3 font-semibold hidden sm:table-cell">Sifat</th>
                        <th class="px-4 py-3 font-semibold">Mood</th>
                        <th class="px-4 py-3 font-semibold">Impulsif</th>
                        <th class="px-4 py-3 font-semibold hidden lg:table-cell">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($summary['transactions'] as $t)
                    <tr class="border-t border-slate-100 hover:bg-slate-50/80">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $t['recorded_at'] }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold {{ $t['type'] === 'Pemasukan' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $t['type'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $t['category'] }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-slate-600">{{ $t['sub_category'] }}</td>
                        <td class="px-4 py-3 text-right font-bold text-navy-800">{{ $fmt($t['amount']) }}</td>
                        <td class="px-4 py-3 hidden sm:table-cell text-slate-600">{{ $t['nature'] }}</td>
                        <td class="px-4 py-3">{{ $t['mood'] }}</td>
                        <td class="px-4 py-3">
                            @if($t['is_impulsive'])
                                <span class="inline-flex items-center gap-0.5 text-rose-600 font-bold text-xs">
                                    <span class="material-symbols-outlined text-sm">bolt</span> Yes
                                </span>
                            @else
                                <span class="text-slate-400 text-xs">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell max-w-xs truncate text-slate-600">{{ $t['notes'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
