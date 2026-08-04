@php
    use App\Models\Setting;

    $tax = $summary['tax_health'] ?? ['amount' => 0, 'count' => 0, 'share_of_income' => 0, 'status' => 'empty', 'status_label' => ''];
    $taxTitle = Setting::val('portal.tax_health_title', 'Kesehatan Pajak') ?: 'Kesehatan Pajak';
    $taxBody = Setting::val(
        'portal.tax_health_body',
        'Pantau kewajiban pajak (PPh 25/29) terpisah dari 4 bucket. Estimasi pajak bukan angka final — diskusikan dengan tax planner YFD agar cadangan dan pelaporan tetap sehat.'
    );
    $taxCta = Setting::val('portal.tax_health_cta', 'Konsultasi Tax Planner') ?: 'Konsultasi Tax Planner';
    $taxWaMsg = Setting::val(
        'portal.tax_health_wa_message',
        'Halo YFD, saya ingin konsultasi kesehatan pajak / tax planner (PPh 25/29 & perencanaan pajak).'
    );
    $waNum = Setting::val('contact.wa_number', '6285111228911') ?: '6285111228911';
    $taxWaUrl = 'https://wa.me/'.$waNum.'?text='.rawurlencode((string) $taxWaMsg);
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-navy-800 text-xl">account_balance</span>
                <div class="text-sm font-semibold text-navy-800">{{ $taxTitle }}</div>
            </div>
            <p class="text-sm text-slate-600 leading-relaxed mb-3">{{ $taxBody }}</p>
            <div class="flex flex-wrap gap-4 text-sm">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Tercatat periode ini</div>
                    <div class="text-lg font-extrabold text-navy-800">{{ $fmt((int) ($tax['amount'] ?? 0)) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">vs pemasukan</div>
                    <div class="text-lg font-extrabold text-navy-800">{{ number_format((float) ($tax['share_of_income'] ?? 0), 1) }}%</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Transaksi</div>
                    <div class="text-lg font-extrabold text-navy-800">{{ (int) ($tax['count'] ?? 0) }}</div>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">{{ $tax['status_label'] ?? '' }}</p>
        </div>
        <div class="shrink-0">
            <a href="{{ $taxWaUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 rounded-lg bg-navy-800 text-white px-4 py-2.5 text-sm font-semibold hover:bg-navy-700 transition-colors">
                <span class="material-symbols-outlined text-base">chat</span>
                {{ $taxCta }}
            </a>
            <p class="text-[11px] text-slate-400 mt-2 max-w-[220px]">Pintu referral ke tax planner partner YFD.</p>
        </div>
    </div>
</div>
