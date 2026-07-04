@if(($needsFinancialDiagnostic ?? false) || ($needsFtsa ?? false))
<div class="space-y-4 mb-6">
    @if($needsFinancialDiagnostic ?? false)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-amber-900">
                @if($isFtsaOnlyPortalUser ?? false)
                    <div class="font-bold">Langkah 1 — Diagnostik Tahap Keuangan</div>
                    <div class="mt-0.5">Jawab pertanyaan tahap keuangan Anda (tanpa angka snapshot).</div>
                @else
                    <div class="font-bold">Langkah 1 — Baseline Data</div>
                    <div class="mt-0.5">Isi diagnostik tahap keuangan + snapshot angka (pendapatan, tabungan, utang, proteksi).</div>
                @endif
            </div>
            <a href="{{ ($isFtsaOnlyPortalUser ?? false) ? route('portal.diagnostic') : route('portal.baseline.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">fact_check</span>
                {{ ($isFtsaOnlyPortalUser ?? false) ? 'Isi Diagnostik' : 'Isi Baseline Data' }}
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
