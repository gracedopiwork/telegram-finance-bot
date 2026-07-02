@php
    $active = $active ?? 'dashboard';
    $currentMonth = $summary['month'] ?? ($assessment['month'] ?? now()->format('Y-m'));
    $currentPeriod = $currentPeriod ?? ($summary['period_months'] ?? ($assessment['period_months'] ?? 1));
    $query = ['month' => $currentMonth, 'period' => $currentPeriod];
@endphp
<div class="p-5 border-b border-white/10">
    <div class="flex items-center gap-3">
        <img src="{{ asset($yfd['logo'] ?? 'images/yfd-logo.png') }}" alt="{{ $yfd['short'] ?? 'YFD' }}" class="h-11 w-auto rounded-lg bg-white/95 px-1.5 py-1 shrink-0">
        <div class="min-w-0">
            <div class="text-[10px] uppercase tracking-[0.18em] text-gold-400 font-bold leading-tight">Your Financial Doctor</div>
            <div class="text-sm font-extrabold leading-tight text-white/95">First Aid Dashboard</div>
        </div>
    </div>
</div>
<nav class="p-3 space-y-0.5 flex-1 text-sm overflow-y-auto">
    @if($hasBotPortalAccess ?? true)
    <a href="{{ route('portal.transactions', $query) }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'transactions' ? 'nav-active font-semibold' : 'hover:bg-white/10' }}">
        <span class="material-symbols-outlined text-lg opacity-80">edit_note</span>
        INPUT DATA
    </a>
    @endif
    @php
        $baselineNavUrl = ($isFtsaOnlyPortalUser ?? false)
            ? ($baselineUrl ?? route('portal.baseline.create'))
            : route('portal.baseline');
        $baselineNavHighlight = ($needsBaseline ?? false)
            || (($needsFtsa ?? false) && !($ftsaRetakeLocked ?? false))
            || (($isFtsaOnlyPortalUser ?? false) && ($needsFinancialDiagnostic ?? false));
        $baselineNavLabel = ($isFtsaOnlyPortalUser ?? false)
            ? (($needsFinancialDiagnostic ?? false) ? 'DIAGNOSTIK KEUANGAN' : 'FTSA 1–32')
            : 'BASELINE DATA (WAJIB DI ISI)';
    @endphp
    <a href="{{ $baselineNavUrl }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'baseline' ? 'nav-active font-semibold' : 'hover:bg-white/10' }} {{ $baselineNavHighlight ? 'ring-2 ring-gold-400/80 bg-gold-400/10' : '' }}">
        <span class="material-symbols-outlined text-lg opacity-80">fact_check</span>
        <span class="flex-1">{{ $baselineNavLabel }}</span>
        @if($baselineNavHighlight)
            <span class="text-[9px] bg-gold-400 text-navy-900 px-1.5 py-0.5 rounded font-bold animate-pulse">ISI</span>
        @endif
    </a>
    @if($hasBotPortalAccess ?? true)
    <a href="{{ route('portal.dashboard', $query) }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'dashboard' ? 'nav-active font-semibold' : 'hover:bg-white/10' }}">
        <span class="material-symbols-outlined text-lg opacity-80">dashboard</span>
        FINANCIAL HEALTH DASHBOARD
    </a>
    @endif
    <a href="{{ route('portal.emotional', $query) }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'emotional' ? 'nav-active font-semibold' : 'hover:bg-white/10' }}">
        <span class="material-symbols-outlined text-lg opacity-80">psychology</span>
        <span class="flex-1">{{ ($isFtsaOnlyPortalUser ?? false) ? 'HASIL FTSA' : 'BEHAVIORAL FINANCIAL DASHBOARD' }}</span>
    </a>
    @if(!($isFtsaOnlyPortalUser ?? false))
    <a href="{{ route('portal.premium') }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'premium' ? 'nav-active font-semibold' : 'hover:bg-white/10' }}">
        <span class="material-symbols-outlined text-lg opacity-80">monitor_heart</span>
        <span class="flex-1">YOUR FINANCIAL HEALTH INDEX</span>
        <span class="text-[9px] bg-gold-400/20 text-gold-400 px-1.5 py-0.5 rounded font-bold">PREMIUM</span>
    </a>
    @endif
    <div class="flex items-center gap-2 rounded-lg px-3 py-3 text-white/35 cursor-not-allowed">
        <span class="material-symbols-outlined text-lg">flag</span>
        <span class="flex-1">YOUR FINANCIAL GOAL PLANNING</span>
        <span class="text-[9px] bg-white/10 text-gold-400 px-1.5 py-0.5 rounded font-bold">PREMIUM</span>
    </div>
</nav>
<div class="p-4 m-3 rounded-xl bg-white/5 border border-white/10 text-xs text-white/75 italic leading-relaxed">
    "Kesehatan finansial yang baik dimulai dari kesadaran hari ini."
    <span class="block mt-1 not-italic text-white/50">— dr. Financial</span>
</div>
