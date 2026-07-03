@php
    $baselineUrl = $baselineUrl ?? route('portal.baseline.create');
    $compact = $compact ?? false;
@endphp
<div class="rounded-2xl border-2 border-gold-400/60 bg-gradient-to-br from-navy-800 to-navy-700 text-white shadow-lg overflow-hidden">
    <div class="px-5 sm:px-6 py-4 border-b border-white/10 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-[10px] uppercase tracking-widest text-gold-400 font-bold">Mulai di sini</div>
            <h2 class="text-lg font-extrabold mt-0.5">Langkah Awal YFD First Aid</h2>
        </div>
        <a href="{{ $baselineUrl }}"
           class="inline-flex items-center gap-2 bg-gold-400 hover:bg-gold-500 text-navy-900 font-bold px-4 py-2.5 rounded-xl text-sm shadow">
            <span class="material-symbols-outlined text-lg">fact_check</span>
            Isi Diagnostik Sekarang
        </a>
    </div>
    <ol class="p-5 sm:p-6 space-y-4 {{ $compact ? 'text-sm' : '' }}">
        <li class="flex gap-3">
            <span class="w-7 h-7 shrink-0 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold text-gold-400">1</span>
            <div>
                <div class="font-semibold">Aktivasi YFD First Aid</div>
                <p class="text-white/75 text-sm mt-0.5">Buka <strong>YFD First Aid</strong> di Telegram → kirim <code class="bg-white/10 px-1 rounded text-gold-300">/activate KODE-LISENSI</code> (dari email pembayaran).</p>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="w-7 h-7 shrink-0 rounded-full bg-gold-400 text-navy-900 flex items-center justify-center text-xs font-bold">2</span>
            <div>
                <div class="font-semibold text-gold-300">Isi Baseline Data (Diagnostik) — wajib</div>
                <p class="text-white/75 text-sm mt-0.5">
                    Menu kiri <strong>BASELINE DATA (WAJIB DI ISI)</strong> → jawab pertanyaan tahap keuangan & snapshot.
                    Tanpa ini, prescription bucket dan diagnosis personal belum aktif.
                </p>
                <a href="{{ $baselineUrl }}" class="inline-flex items-center gap-1 text-gold-400 font-semibold text-sm mt-2 hover:underline">
                    Buka form diagnostik <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="w-7 h-7 shrink-0 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold text-gold-400">3</span>
            <div>
                <div class="font-semibold">Catat transaksi lewat YFD First Aid</div>
                <p class="text-white/75 text-sm mt-0.5">Kirim catatan harian di Telegram (contoh: <em>kopi 25rb</em>) atau import CSV di menu <strong>INPUT DATA</strong>.</p>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="w-7 h-7 shrink-0 rounded-full bg-white/15 flex items-center justify-center text-xs font-bold text-gold-400">4</span>
            <div>
                <div class="font-semibold">Pantau dashboard</div>
                <p class="text-white/75 text-sm mt-0.5">Setelah ada data, Financial Health Dashboard & Behavioral Dashboard terisi otomatis.</p>
            </div>
        </li>
    </ol>
</div>
