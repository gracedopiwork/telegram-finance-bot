<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 sm:p-12 text-center">
    <div class="w-16 h-16 mx-auto rounded-2xl bg-navy-800/5 flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-4xl text-navy-800">{{ !empty($actionUrl) ? 'health_and_safety' : 'chat' }}</span>
    </div>
    <h3 class="text-lg font-bold text-navy-800">{{ $title ?? 'Belum ada data' }}</h3>
    <p class="text-sm text-slate-600 mt-2 max-w-md mx-auto">{{ $message ?? 'Catat transaksi via YFD First Aid di Telegram. Data akan otomatis muncul di dashboard ini.' }}</p>
    @if(!empty($actionUrl))
        <a href="{{ $actionUrl }}"
           class="inline-flex items-center gap-2 mt-5 bg-navy-800 hover:bg-navy-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm">
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
            {{ $actionLabel ?? 'Lanjutkan' }}
        </a>
    @endif
</div>
