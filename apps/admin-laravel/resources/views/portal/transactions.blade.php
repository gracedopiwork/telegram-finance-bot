@extends('portal.layouts.app')

@section('title', 'Data Transaksi — YFD')
@section('heading', 'Input Data — Riwayat Transaksi')

@section('content')
@php
    $fmt = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $baseline = $summary['baseline'] ?? null;
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

{{-- Import CSV --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b bg-slate-50 flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-bold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined">upload_file</span>
            Import Transaksi (CSV)
        </h3>
        <a href="{{ route('portal.transactions.template') }}"
           class="inline-flex items-center gap-1 text-sm font-semibold text-navy-800 hover:underline">
            <span class="material-symbols-outlined text-base">download</span>
            Unduh template CSV
        </a>
    </div>
    <div class="p-5 sm:p-6">
        <p class="text-sm text-slate-600 mb-4">
            Isi data di Excel/Google Sheets lalu simpan sebagai <strong>CSV UTF-8</strong>.
            Kolom: tanggal, jenis, kategori, sub_kategori, nominal, sifat, mood, impulsif, keterangan.
            Maks. 500 baris per file.
        </p>
        @if(session('import_errors'))
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-900 max-h-40 overflow-y-auto">
                <div class="font-semibold mb-1">Detail error:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="post" action="{{ route('portal.transactions.import', request()->only(['month', 'period'])) }}"
              enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-end gap-3">
            @csrf
            <div class="flex-1 min-w-0">
                <label class="block text-sm font-medium text-slate-700 mb-1">File CSV</label>
                <input type="file" name="file" accept=".csv,text/csv"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-navy-800 file:text-white file:font-semibold hover:file:bg-navy-700"
                       required>
                @error('file')
                    <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-5 py-2.5 rounded-xl shrink-0">
                <span class="material-symbols-outlined">upload</span>
                Import
            </button>
        </form>
    </div>
</div>

@if($baseline)
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="bg-slate-50 px-5 py-4 border-b flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-bold text-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined">inventory_2</span>
            Baseline Snapshot (Sheet 1A)
        </h3>
        <span class="text-xs text-slate-500">Diperbarui: {{ $baseline['assessed_at'] }} · {{ $baseline['stage_label'] }}</span>
    </div>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        @if($baseline['current_goal'])
            <div class="sm:col-span-2 lg:col-span-4 rounded-xl bg-gold-50 border border-gold-200 p-3">
                <div class="text-xs font-bold text-amber-800 uppercase">Current Goal</div>
                <div class="font-medium text-navy-800 mt-1">{{ $baseline['current_goal'] }}</div>
            </div>
        @endif
        @foreach([
            'avg_monthly_income' => 'Pendapatan/bulan',
            'emergency_fund' => 'Dana darurat',
            'cash_savings' => 'Tabungan',
            'total_investment' => 'Investasi',
            'total_asset' => 'Total aset',
            'total_debt' => 'Total utang',
        ] as $key => $label)
            @if($baseline[$key])
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                    <div class="font-bold text-navy-800">{{ $fmt((int) $baseline[$key]) }}</div>
                </div>
            @endif
        @endforeach
        <div class="rounded-xl bg-slate-50 p-3">
            <div class="text-xs text-slate-500 mb-1">Proteksi</div>
            <div class="flex flex-wrap gap-1">
                @if($baseline['protection']['bpjs'])<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">BPJS</span>@endif
                @if($baseline['protection']['health'])<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Kesehatan</span>@endif
                @if($baseline['protection']['income'])<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Income</span>@endif
                @if($baseline['protection']['life'])<span class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Jiwa</span>@endif
            </div>
        </div>
        @if($baseline['dominant_archetype_label'])
            <div class="rounded-xl bg-navy-800 text-white p-3">
                <div class="text-xs text-white/70">FTSA Archetype</div>
                <div class="font-bold">{{ $baseline['dominant_archetype_label'] }}</div>
            </div>
        @endif
    </div>
</div>
@else
    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
        Belum ada baseline snapshot. <a href="{{ route('portal.baseline.create') }}" class="font-semibold underline">Isi Health Check-Up</a> untuk melengkapi data fondasi.
    </div>
@endif

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
        <div class="text-xs text-slate-500 mt-1">Saving rate · {{ $summary['period_label'] }}</div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b flex items-center justify-between">
        <h3 class="font-bold text-navy-800">Tabel Transaksi <span class="text-slate-400 font-normal text-sm">({{ $summary['period_label'] }})</span></h3>
        <a href="{{ route('portal.dashboard', ['month' => $summary['month'], 'period' => $summary['period_months']]) }}" class="text-sm text-navy-800 font-semibold hover:underline">Lihat Dashboard →</a>
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
                        <th class="px-4 py-3 font-semibold hidden lg:table-cell">Bucket</th>
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
                        <td class="px-4 py-3 hidden lg:table-cell text-xs text-slate-600">{{ $t['bucket'] ?? '—' }}</td>
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
