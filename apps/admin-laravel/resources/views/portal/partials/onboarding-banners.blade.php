@if(($needsFinancialDiagnostic ?? false) || ($needsFtsa ?? false) || ($needsBaseline ?? false))
<div class="space-y-4 mb-6">
    @if($isFtsaOnlyPortalUser ?? false)
        @if($needsFinancialDiagnostic ?? false)
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-sky-900">
                <div class="font-bold">Langkah 1 — Diagnostik keuangan</div>
                <div class="mt-0.5">Isi tahap kesehatan finansial sebelum kuesioner FTSA.</div>
            </div>
            <a href="{{ $portalDiagnosticUrl ?? route('portal.diagnostic') }}"
               class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">health_and_safety</span>
                Isi Diagnostik
            </a>
        </div>
        @endif
    @elseif(($needsFinancialDiagnostic ?? false) || ($needsBaseline ?? false))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-amber-900">
                <div class="font-bold">Langkah 1 — Baseline Data</div>
                <div class="mt-0.5">
                    @if($needsFinancialDiagnostic ?? false)
                        Isi diagnostik tahap keuangan + snapshot angka (pendapatan, tabungan, utang, proteksi).
                    @else
                        Diagnostik sudah tersimpan — lengkapi snapshot angka langsung di dashboard ini.
                    @endif
                </div>
            </div>
            <a href="{{ ($needsBaseline ?? false) && !($needsFinancialDiagnostic ?? false)
                    ? (route('portal.dashboard', request()->only(['month', 'period'])) . '#baseline-snapshot')
                    : route('portal.baseline.create') }}"
               class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2 rounded-xl text-sm shrink-0">
                <span class="material-symbols-outlined text-lg">fact_check</span>
                {{ ($needsBaseline ?? false) && !($needsFinancialDiagnostic ?? false) ? 'Isi Snapshot' : 'Isi Baseline Data' }}
            </a>
        </div>
    @endif

    @if($needsFtsa ?? false)
        <div class="rounded-2xl border border-gold-400/50 bg-gold-400/10 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-navy-900">
                <div class="font-bold">Langkah 2 — FTSA 1–32 (opsional)</div>
                <div class="mt-0.5">Kuesioner behavioral terpisah dari baseline. Evaluasi ulang setiap 12 bulan setelah unlock premium.</div>
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
