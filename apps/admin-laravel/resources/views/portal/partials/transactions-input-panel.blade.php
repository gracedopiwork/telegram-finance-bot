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
            <a href="{{ route('portal.transactions.export') }}"
               class="inline-flex items-center gap-1 rounded-lg border border-navy-200 bg-navy-50 text-navy-800 hover:bg-navy-100 px-3 py-2 text-xs font-semibold">
                <span class="material-symbols-outlined text-sm">download</span>
                Export Semua Excel
            </a>
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
            'message' => 'Catat via bot Telegram di atas. Lihat ringkasan di Financial Health Dashboard.',
        ])
    @else
        @php
            $txCategories = collect($summary['transactions'])->pluck('category')->filter()->unique()->sort()->values();
            $txBuckets = collect($summary['transactions'])->pluck('bucket')->filter()->unique()->sort()->values();
        @endphp
        <div class="px-5 py-3 border-b bg-slate-50/80 flex flex-wrap items-end gap-3">
            <div class="min-w-[10rem]">
                <label for="tx-filter-category" class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Filter kategori</label>
                <select id="tx-filter-category" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Semua kategori</option>
                    @foreach($txCategories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[10rem]">
                <label for="tx-filter-bucket" class="block text-[11px] font-semibold text-slate-500 uppercase mb-1">Filter bucket</label>
                <select id="tx-filter-bucket" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Semua bucket</option>
                    @foreach($txBuckets as $bucket)
                        <option value="{{ $bucket }}">{{ $bucket }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-slate-500 pb-2">
                Klik header kolom untuk sort. Filter bucket mis. <strong>Essential Living</strong> untuk evaluasi alokasi.
            </p>
            <p id="tx-filter-count" class="text-xs font-semibold text-navy-800 pb-2 ml-auto"></p>
        </div>
        <div class="overflow-x-auto">
            <table id="tx-table" class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-semibold w-10">
                            <input type="checkbox" id="tx-select-all"
                                   class="rounded border-slate-300 text-navy-700 focus:ring-navy-500"
                                   aria-label="Pilih semua transaksi">
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" class="tx-sort inline-flex items-center gap-0.5 hover:text-navy-800" data-sort="date" data-type="date">
                                Tanggal <span class="material-symbols-outlined text-sm tx-sort-icon opacity-40">unfold_more</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" class="tx-sort inline-flex items-center gap-0.5 hover:text-navy-800" data-sort="type" data-type="text">
                                Jenis <span class="material-symbols-outlined text-sm tx-sort-icon opacity-40">unfold_more</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" class="tx-sort inline-flex items-center gap-0.5 hover:text-navy-800" data-sort="category" data-type="text">
                                Kategori <span class="material-symbols-outlined text-sm tx-sort-icon opacity-40">unfold_more</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold text-right">
                            <button type="button" class="tx-sort inline-flex items-center gap-0.5 hover:text-navy-800 ml-auto" data-sort="amount" data-type="number">
                                Nominal <span class="material-symbols-outlined text-sm tx-sort-icon opacity-40">unfold_more</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold hidden lg:table-cell">
                            <button type="button" class="tx-sort inline-flex items-center gap-0.5 hover:text-navy-800" data-sort="bucket" data-type="text">
                                Bucket <span class="material-symbols-outlined text-sm tx-sort-icon opacity-40">unfold_more</span>
                            </button>
                        </th>
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
                        data-tx-amount="{{ $t['amount'] }}"
                        data-tx-date="{{ $t['recorded_at'] }}"
                        data-tx-category="{{ $t['category'] }}"
                        data-tx-bucket="{{ $t['bucket'] ?? '' }}">
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
                                    'Piutang Keluar', 'Piutang Masuk' => 'bg-violet-50 text-violet-800',
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
