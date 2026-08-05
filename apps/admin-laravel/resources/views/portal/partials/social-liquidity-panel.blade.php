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
    ];
    $liqTitle = Setting::val('portal.social_liquidity_title', 'Likuiditas Sosial') ?: 'Likuiditas Sosial';
    $liqBody = Setting::val(
        'portal.social_liquidity_body',
        'Arus kas karena hubungan sosial: piutang (kamu meminjamkan) dan hutang (kamu menerima pinjaman). Tidak masuk 4 bucket prescription.'
    );
    $statusTone = match ($liq['status'] ?? 'empty') {
        'critical' => 'border-rose-200 bg-rose-50 text-rose-900',
        'watch' => 'border-amber-200 bg-amber-50 text-amber-900',
        'ok' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        default => 'border-slate-200 bg-slate-50 text-slate-700',
    };
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-center gap-2 mb-2">
        <span class="material-symbols-outlined text-navy-800 text-xl">handshake</span>
        <div class="text-sm font-semibold text-navy-800">{{ $liqTitle }}</div>
    </div>
    <p class="text-sm text-slate-600 leading-relaxed mb-4">{{ $liqBody }}</p>

    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Piutang (kamu meminjamkan)</div>
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
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Direlakan</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['written_off_month'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">periode ini</div>
        </div>
    </div>

    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Hutang (kamu menerima pinjaman)</div>
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 text-sm mb-4">
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Hutang masuk</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['borrow_month'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">likuiditas naik (bukan income)</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Hutang keluar</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['repay_debt_month'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">bayar balik periode ini</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Hutang aktif</div>
            <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($liq['active_debt_total'] ?? 0)) }}</div>
            <div class="text-xs text-slate-500">{{ (int) ($liq['count_active_debt'] ?? 0) }} hutang</div>
        </div>
    </div>

    <div class="rounded-lg border px-3 py-2 text-xs {{ $statusTone }}">
        {{ $liq['status_label'] ?? '' }}
        <span class="block mt-1 opacity-80">Tidak masuk Budget Prescription — konteks kapasitas finansial saja.</span>
    </div>
</div>
