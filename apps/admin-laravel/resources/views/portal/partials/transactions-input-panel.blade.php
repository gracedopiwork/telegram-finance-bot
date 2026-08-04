@php
    $summary = $summary ?? [];
    $fmt = $fmt ?? fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');
    $showBotBanner = $showBotBanner ?? true;
    $dashboardLink = $dashboardLink ?? false;
@endphp

@if($showBotBanner)
<div class="bg-gradient-to-r from-navy-800 to-navy-600 rounded-2xl p-5 sm:p-6 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-3xl text-gold-400">send</span>
        <div>
            <h3 class="font-bold text-lg">Catat via YFD First Aid</h3>
            <p class="text-sm text-white/80 mt-1">Kirim teks atau foto struk. Dokter Finansial akan merapikan pencatatan kamu.</p>
        </div>
    </div>
    <div class="text-sm bg-white/10 rounded-xl px-4 py-2 font-mono shrink-0">/catat makan siang 35rb</div>
</div>
@endif

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
            Isi data di Excel/Google Sheets lalu simpan sebagai <strong>CSV UTF-8</strong> (koma atau titik-koma).
            Kolom: tanggal, <strong>jenis</strong> (Pemasukan / Pengeluaran / Saving/Investment / Kewajiban Pajak), kategori, nominal,
            <strong>sifat</strong> (Need / Wants), mood (atau Mood Spending), impulsif, keterangan.
            File Excel: simpan sebagai <strong>CSV UTF-8</strong> (titik-koma atau koma) — ekstensi .csv / .txt / .xls didukung.
            Kategori resmi (closed list): Makanan & Minuman, Transportasi, Tempat Tinggal, Lifestyle & Hiburan, Sosial & Keluarga, Bisnis & Karir, Gaji, dll.
            Nominal: angka polos (<code>35000</code>) atau format Indonesia (<code>35.000</code>, <code>35rb</code>).
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
                <input type="file" name="file" accept=".csv,.txt,.xls,.xlsx,text/csv"
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

<div id="tx-delete-toast" class="hidden fixed bottom-6 right-6 z-50 rounded-xl bg-navy-800 text-white text-sm px-4 py-3 shadow-lg"></div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-bold text-navy-800">
            Tabel Transaksi
            <span class="text-slate-400 font-normal text-sm">({{ $summary['period_label'] ?? '—' }})</span>
            @if(($summary['transactions_total'] ?? 0) > ($summary['transactions_shown'] ?? count($summary['transactions'] ?? [])))
                <span class="block text-xs font-normal text-amber-700 mt-1">
                    Menampilkan {{ $summary['transactions_shown'] ?? count($summary['transactions'] ?? []) }}
                    dari {{ $summary['transactions_total'] }} transaksi — gunakan filter bulan jika data tidak muncul.
                </span>
            @elseif(($summary['transactions_total'] ?? 0) > 0)
                <span class="block text-xs font-normal text-slate-500 mt-1">
                    {{ $summary['transactions_total'] }} transaksi pada periode ini.
                </span>
            @endif
        </h3>
        <div class="flex flex-wrap items-center gap-2">
            @if(!empty($summary['transactions']))
                <button type="button"
                        id="tx-delete-selected-btn"
                        data-url="{{ route('portal.transactions.destroy-selected', ['month' => request('month', $summary['month'] ?? null), 'period' => request('period', $summary['period_months'] ?? 1)]) }}"
                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 disabled:opacity-50 disabled:cursor-not-allowed px-3 py-2 text-xs font-semibold"
                        disabled>
                    <span class="material-symbols-outlined text-sm">delete_sweep</span>
                    Hapus Terpilih
                </button>
                <button type="button"
                        id="tx-delete-month-btn"
                        data-month="{{ request('month', $summary['month'] ?? now()->format('Y-m')) }}"
                        data-month-label="{{ \Carbon\Carbon::createFromFormat('Y-m', request('month', $summary['month'] ?? now()->format('Y-m')))->translatedFormat('F Y') }}"
                        data-url="{{ route('portal.transactions.destroy-month', ['month' => request('month', $summary['month'] ?? null), 'period' => request('period', $summary['period_months'] ?? 1)]) }}"
                        class="inline-flex items-center gap-1 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 disabled:opacity-50 disabled:cursor-not-allowed px-3 py-2 text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    Hapus Semua Bulan Ini
                </button>
            @endif
            @if($dashboardLink ?? false)
                <a href="{{ route('portal.dashboard', ['month' => $summary['month'] ?? null, 'period' => $summary['period_months'] ?? 1]) }}"
                   class="text-sm text-navy-800 font-semibold hover:underline">Lihat Dashboard →</a>
            @endif
        </div>
    </div>

    @if(empty($summary['transactions']))
        @include('portal.partials.empty-state', [
            'title' => 'Belum ada transaksi',
            'message' => 'Catat via bot di atas atau import CSV. Lihat ringkasan di Financial Health Dashboard.',
        ])
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-semibold w-10">
                            <input type="checkbox" id="tx-select-all"
                                   class="rounded border-slate-300 text-navy-700 focus:ring-navy-500"
                                   aria-label="Pilih semua transaksi">
                        </th>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Jenis</th>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 font-semibold text-right">Nominal</th>
                        <th class="px-4 py-3 font-semibold hidden lg:table-cell">Bucket</th>
                        <th class="px-4 py-3 font-semibold hidden sm:table-cell">Sifat</th>
                        <th class="px-4 py-3 font-semibold">Mood</th>
                        <th class="px-4 py-3 font-semibold">Impulsif</th>
                        <th class="px-4 py-3 font-semibold hidden lg:table-cell">Keterangan</th>
                        <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tx-table-body">
                @foreach($summary['transactions'] as $t)
                    <tr class="border-t border-slate-100 hover:bg-slate-50/80 transition-opacity"
                        data-tx-row
                        data-tx-id="{{ $t['id'] }}"
                        data-tx-type="{{ $t['type'] }}"
                        data-tx-amount="{{ $t['amount'] }}">
                        <td class="px-4 py-3 align-top">
                            <input type="checkbox"
                                   class="tx-select-item rounded border-slate-300 text-navy-700 focus:ring-navy-500"
                                   value="{{ $t['id'] }}"
                                   aria-label="Pilih transaksi {{ $t['id'] }}">
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $t['recorded_at'] }}</td>
                        <td class="px-4 py-3">
                            @php
                                $typeClass = match($t['type']) {
                                    'Pemasukan' => 'bg-emerald-50 text-emerald-700',
                                    'Saving/Investment' => 'bg-sky-50 text-sky-800',
                                    'Kewajiban Pajak' => 'bg-amber-50 text-amber-800',
                                    default => 'bg-rose-50 text-rose-700',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold {{ $typeClass }}">
                                {{ $t['type'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $t['category'] }}</td>
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
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                    data-delete-tx
                                    data-url="{{ route('portal.transactions.destroy', ['transaction' => $t['id'], 'month' => request('month', $summary['month'] ?? null), 'period' => request('period', $summary['period_months'] ?? 1)]) }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 disabled:opacity-50 disabled:cursor-wait px-3 py-1.5 text-xs font-semibold">
                                <span class="material-symbols-outlined text-sm">delete</span>
                                Hapus
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
