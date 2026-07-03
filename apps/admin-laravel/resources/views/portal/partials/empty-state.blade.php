<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 sm:p-12 text-center">
    <div class="w-16 h-16 mx-auto rounded-2xl bg-navy-800/5 flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-4xl text-navy-800">chat</span>
    </div>
    <h3 class="text-lg font-bold text-navy-800">{{ $title ?? 'Belum ada data' }}</h3>
    <p class="text-sm text-slate-600 mt-2 max-w-md mx-auto">{{ $message ?? 'Catat transaksi via YFD First Aid di Telegram. Data akan otomatis muncul di dashboard ini.' }}</p>
    <p class="text-xs text-slate-500 mt-4">Contoh: <code class="bg-slate-100 px-2 py-1 rounded">/catat makan siang 35rb</code></p>
</div>
