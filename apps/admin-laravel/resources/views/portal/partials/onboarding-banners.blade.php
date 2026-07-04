@if(($needsFinancialDiagnostic ?? false) || ($needsFtsaSnapshot ?? false) || ($needsFtsa ?? false))
<div class="space-y-4 mb-6">
    @if($isFtsaOnlyPortalUser ?? false)
        @if($needsFtsaSnapshot ?? false)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-amber-900">
                <div class="font-bold">Langkah 1 — Snapshot angka keuangan</div>
                <div class="mt-0.5">Pendapatan, tabungan, utang, dan dana darurat.</div>
            </div>
            <a href="{{ route('portal.baseline.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">inventory_2</span>
                Isi Snapshot
            </a>
        </div>
        @endif
    @elseif($needsFinancialDiagnostic ?? false)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-amber-900">
                <div class="font-bold">Langkah 1 — Baseline Data</div>
                <div class="mt-0.5">Isi diagnostik tahap keuangan + snapshot angka (pendapatan, tabungan, utang, proteksi).</div>
            </div>
            <a href="{{ route('portal.baseline.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">fact_check</span>
                Isi Baseline Data
            </a>
        </div>
    @endif

    @if($needsFtsa ?? false)
        <div class="rounded-2xl border border-gold-400/50 bg-gold-400/10 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-navy-900">
                <div class="font-bold">{{ ($isFtsaOnlyPortalUser ?? false) ? 'Langkah 2 — FTSA 1–32' : 'Langkah 2 — FTSA 1–32 (opsional)' }}</div>
                <div class="mt-0.5">Kuesioner behavioral finansial. Evaluasi ulang setiap 12 bulan.</div>
            </div>
            <a href="{{ $portalFtsaUrl ?? route('portal.ftsa.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">psychology</span>
                Isi FTSA
            </a>
        </div>
    @endif
</div>
@endif
