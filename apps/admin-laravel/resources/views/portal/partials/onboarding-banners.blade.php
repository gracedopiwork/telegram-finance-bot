@if(($needsFinancialDiagnostic ?? false) || ($needsFtsa ?? false) || ($needsBaseline ?? false))
<div class="space-y-4 mb-6">
    @if($needsFinancialDiagnostic ?? false)
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-sky-900">
                <div class="font-bold">Langkah 1 — Diagnostik keuangan</div>
                <div class="mt-0.5">Isi tahap kesehatan finansial Anda. Bisa dilakukan kapan saja dari portal.</div>
            </div>
            <a href="{{ $portalDiagnosticUrl ?? route('portal.diagnostic') }}"
               class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">health_and_safety</span>
                Isi Diagnostik
            </a>
        </div>
    @endif

    @if($needsFtsa ?? false)
        <div class="rounded-2xl border border-gold-400/50 bg-gold-400/10 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-navy-900">
                <div class="font-bold">Langkah {{ ($needsFinancialDiagnostic ?? false) ? '2' : '1' }} — FTSA 1–32</div>
                <div class="mt-0.5">Lengkapi kuesioner behavioral untuk melihat profil archetype finansial Anda.</div>
            </div>
            <a href="{{ $portalFtsaUrl ?? route('portal.baseline.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">psychology</span>
                Isi FTSA
            </a>
        </div>
    @endif

    @if(($needsBaseline ?? false) && !($needsFinancialDiagnostic ?? false) && !($isFtsaOnlyPortalUser ?? false))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-amber-900">
                <div class="font-bold">Lengkapi Baseline Data</div>
                <div class="mt-0.5">Dashboard personal aktif penuh setelah diagnostik & snapshot keuangan diisi di portal.</div>
            </div>
            <a href="{{ $portalBaselineUrl ?? route('portal.baseline.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">fact_check</span>
                Buka Baseline Data
            </a>
        </div>
    @endif
</div>
@endif
