@php
    use App\Models\Setting;

    $liq = $summary['social_liquidity'] ?? [
        'outbound_month' => 0,
        'outbound_share' => 0,
        'repaid_month' => 0,
        'repaid_share_of_outbound' => 0,
        'active_total' => 0,
        'written_off_month' => 0,
        'status' => 'empty',
        'status_label' => '',
        'count_outbound' => 0,
        'count_active' => 0,
        'borrow_month' => 0,
        'repay_debt_month' => 0,
        'active_debt_total' => 0,
        'count_active_debt' => 0,
        'overdue_receivable_total' => 0,
        'overdue_payable_total' => 0,
        'tracker_receivables' => [],
        'tracker_payables' => [],
    ];
    $liqTitle = Setting::val('portal.social_liquidity_title', 'Likuiditas Sosial') ?: 'Likuiditas Sosial';
    $liqBody = Setting::val(
        'portal.social_liquidity_body',
        'Arus kas karena hubungan sosial: piutang (kamu meminjamkan) dan utang (kamu menerima pinjaman). Tidak masuk 4 bucket prescription.'
    );
    $statusTone = match ($liq['status'] ?? 'empty') {
        'critical' => 'border-rose-200 bg-rose-50 text-rose-900',
        'watch' => 'border-amber-200 bg-amber-50 text-amber-900',
        'ok' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        default => 'border-slate-200 bg-slate-50 text-slate-700',
    };
    $receivableRows = $liq['tracker_receivables'] ?? [];
    $payableRows = $liq['tracker_payables'] ?? [];
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-center gap-2 mb-2">
        <span class="material-symbols-outlined text-navy-800 text-xl">handshake</span>
        <div class="text-sm font-semibold text-navy-800">{{ $liqTitle }}</div>
    </div>
    <p class="text-sm text-slate-600 leading-relaxed mb-4">{{ $liqBody }}</p>

    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Piutang (cash keluar)</div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm mb-5">
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Piutang keluar</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['outbound_month'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">{{ number_format((float) ($liq['outbound_share'] ?? 0), 1) }}% income</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Sudah kembali</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['repaid_month'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">{{ number_format((float) ($liq['repaid_share_of_outbound'] ?? 0), 1) }}% dari keluar</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Aktif / belum kembali</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['active_total'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">{{ (int) ($liq['count_active'] ?? 0) }} piutang</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Jatuh tempo</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['overdue_receivable_total'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">siap ditagih</div>
        </div>
    </div>

    <div class="overflow-x-auto mb-6">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Tracker piutang</div>
        @if(empty($receivableRows))
            <p class="text-sm text-slate-500">Belum ada tracker piutang. Contoh catatan bot: “Di pinjam Grace 2,7 jt buat kepentingan kerja. Besok di transfer kembali.”</p>
        @else
            <table class="min-w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Nama</th>
                        <th class="px-3 py-2 font-semibold">Nominal</th>
                        <th class="px-3 py-2 font-semibold">Tujuan</th>
                        <th class="px-3 py-2 font-semibold">Status</th>
                        <th class="px-3 py-2 font-semibold">Tindak lanjut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($receivableRows as $row)
                        <tr class="{{ !empty($row['is_overdue']) ? 'bg-amber-50/60' : '' }}">
                            <td class="px-3 py-2 font-medium text-navy-900">{{ $row['name'] }}</td>
                            <td class="px-3 py-2 tabular-nums">{{ $fmt((int) $row['amount']) }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row['purpose'] }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center gap-1">
                                    @if(($row['status'] ?? '') === 'settled')
                                        <span class="text-emerald-600">✓</span>
                                    @elseif(!empty($row['is_overdue']))
                                        <span class="text-rose-600">●</span>
                                    @elseif(($row['status'] ?? '') === 'disputed')
                                        <span class="text-orange-500">▲</span>
                                    @elseif(($row['status'] ?? '') === 'written_off')
                                        <span class="text-rose-500">×</span>
                                    @else
                                        <span class="text-amber-500">●</span>
                                    @endif
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="text-slate-600 mb-1">{{ $row['follow_up'] }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @if(!empty($row['can_write_off']))
                                        <form method="POST" action="{{ route('portal.social-liquidity.receivables.write-off', $row['id']) }}" onsubmit="return confirm('Relakan piutang ini? Akan dicatat sebagai Pengeluaran Sosial & Keluarga.');">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-rose-700 hover:underline">Relakan</button>
                                        </form>
                                    @endif
                                    @if(!empty($row['can_dispute']))
                                        <form method="POST" action="{{ route('portal.social-liquidity.receivables.dispute', $row['id']) }}" onsubmit="return confirm('Tandai sebagai sengketa?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-orange-700 hover:underline">Sengketa</button>
                                        </form>
                                    @endif
                                    @if(!empty($row['can_delete']))
                                        <form method="POST" action="{{ route('portal.social-liquidity.receivables.destroy', $row['id']) }}" onsubmit="return confirm('Hapus baris piutang ini dari tracker? Jika masih aktif, transaksi buka ikut dihapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-slate-600 hover:underline">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Utang (cash bertambah)</div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm mb-5">
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Utang masuk</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['borrow_month'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">cash naik (bukan income)</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Utang keluar</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['repay_debt_month'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">bayar balik periode ini</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Utang aktif</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['active_debt_total'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">{{ (int) ($liq['count_active_debt'] ?? 0) }} utang</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Jatuh tempo</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['overdue_payable_total'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">siap dibayar</div>
        </div>
    </div>

    <div class="overflow-x-auto mb-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Tracker utang</div>
        @if(empty($payableRows))
            <p class="text-sm text-slate-500">Belum ada tracker utang. Contoh: “Pinjam dari Ayuti 1jt buat biaya RS, bulan depan.”</p>
        @else
            <table class="min-w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Nama</th>
                        <th class="px-3 py-2 font-semibold">Nominal</th>
                        <th class="px-3 py-2 font-semibold">Tujuan</th>
                        <th class="px-3 py-2 font-semibold">Status</th>
                        <th class="px-3 py-2 font-semibold">Tindak lanjut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($payableRows as $row)
                        <tr class="{{ !empty($row['is_overdue']) ? 'bg-amber-50/60' : '' }}">
                            <td class="px-3 py-2 font-medium text-navy-900">{{ $row['name'] }}</td>
                            <td class="px-3 py-2 tabular-nums">{{ $fmt((int) $row['amount']) }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row['purpose'] }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center gap-1">
                                    @if(($row['status'] ?? '') === 'settled')
                                        <span class="text-emerald-600">✓</span>
                                    @elseif(!empty($row['is_overdue']))
                                        <span class="text-rose-600">●</span>
                                    @elseif(($row['status'] ?? '') === 'disputed')
                                        <span class="text-orange-500">▲</span>
                                    @else
                                        <span class="text-amber-500">●</span>
                                    @endif
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="text-slate-600 mb-1">{{ $row['follow_up'] }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @if(!empty($row['can_dispute']))
                                        <form method="POST" action="{{ route('portal.social-liquidity.payables.dispute', $row['id']) }}" onsubmit="return confirm('Tandai sebagai sengketa?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-orange-700 hover:underline">Sengketa</button>
                                        </form>
                                    @endif
                                    @if(!empty($row['can_delete']))
                                        <form method="POST" action="{{ route('portal.social-liquidity.payables.destroy', $row['id']) }}" onsubmit="return confirm('Hapus baris utang ini dari tracker? Jika masih aktif, transaksi buka ikut dihapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-slate-600 hover:underline">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="rounded-lg border px-3 py-2 text-xs {{ $statusTone }}">
        {{ $liq['status_label'] ?? '' }}
        <span class="block mt-1 opacity-80">Tidak masuk Budget Prescription — konteks kapasitas finansial saja. Bot akan mengingatkan saat jatuh tempo.</span>
    </div>
</div>
