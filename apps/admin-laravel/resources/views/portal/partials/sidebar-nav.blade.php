@php
    $active = $active ?? 'dashboard';
    $summary = $summary ?? [];
    $assessment = $assessment ?? [];
    $currentMonth = $summary['month'] ?? $assessment['month'] ?? now()->format('Y-m');
    $currentPeriod = $currentPeriod ?? $summary['period_months'] ?? $assessment['period_months'] ?? 1;
    $query = ['month' => $currentMonth, 'period' => $currentPeriod];
    $dashboardNavHighlight = (bool) ($dashboardNavHighlight ?? (
        !($portalOnboardingComplete ?? false)
        && ($needsBaseline ?? false)
        && ($hasBotPortalAccess ?? false)
    ));
@endphp
<div class="p-5 border-b border-white/10">
    <div class="flex items-center gap-3">
        <img src="{{ asset($yfd['logo'] ?? 'images/yfd-logo.png') }}" alt="{{ $yfd['short'] ?? 'YFD' }}" class="h-11 w-auto rounded-lg bg-white/95 px-1.5 py-1 shrink-0">
        <div class="min-w-0">
            <div class="text-[10px] uppercase tracking-[0.18em] text-gold-400 font-bold leading-tight">Your Financial Doctor</div>
            <div class="text-sm font-extrabold leading-tight text-white/95">{{ ($isFtsaOnlyPortalUser ?? false) ? 'FTSA Premium Dashboard' : 'First Aid Dashboard' }}</div>
        </div>
    </div>
</div>
<nav class="p-3 space-y-0.5 flex-1 text-sm overflow-y-auto">
    @if($hasBotPortalAccess ?? false)
    <a href="{{ $portalTransactionsUrl ?? route('portal.transactions', $query) }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'transactions' ? 'nav-active font-semibold' : 'hover:bg-white/10' }}">
        <span class="material-symbols-outlined text-lg opacity-80">edit_note</span>
        <span class="flex-1">INPUT DATA</span>
    </a>
    @endif
    @include('portal.partials.sidebar-baseline-nav')
    @if($hasBotPortalAccess ?? false)
    <a href="{{ route('portal.dashboard', $query) }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'dashboard' ? 'nav-active font-semibold' : 'hover:bg-white/10' }} {{ $dashboardNavHighlight ? 'ring-2 ring-gold-400/80 bg-gold-400/10' : '' }}">
        <span class="material-symbols-outlined text-lg opacity-80">dashboard</span>
        <span class="flex-1">FINANCIAL HEALTH DASHBOARD</span>
        @if($dashboardNavHighlight)
            <span class="text-[9px] bg-gold-400 text-navy-900 px-1.5 py-0.5 rounded font-bold animate-pulse">ISI</span>
        @endif
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
    @if(!($isFtsaOnlyPortalUser ?? false))
    <div class="flex items-center gap-2 rounded-lg px-3 py-3 text-white/35 cursor-not-allowed">
        <span class="material-symbols-outlined text-lg">flag</span>
        <span class="flex-1">YOUR FINANCIAL GOAL PLANNING</span>
        <span class="text-[9px] bg-white/10 text-gold-400 px-1.5 py-0.5 rounded font-bold">PREMIUM</span>
    </div>
    @endif
</nav>
@include('portal.partials.sidebar-timezone')
<div class="p-4 m-3 rounded-xl bg-white/5 border border-white/10 text-xs text-white/75 italic leading-relaxed">
    "Kesehatan finansial yang baik dimulai dari kesadaran hari ini."
    <span class="block mt-1 not-italic text-white/50">— dr. Financial</span>
</div>
