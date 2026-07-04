@php
    $baselineNavUrl = $baselineUrl ?? route('portal.baseline.create');
    $ftsaOnly = $isFtsaOnlyPortalUser ?? false;
    $showBaselineNav = ! $ftsaOnly
        || ($needsFtsaSnapshot ?? false)
        || ($needsFtsa ?? false);
    $baselineNavHighlight = !($portalOnboardingComplete ?? false) && (
        ($needsBaseline ?? false)
        || (($needsFtsa ?? false) && !($ftsaRetakeLocked ?? false))
        || ($ftsaOnly && ($needsFtsaSnapshot ?? false))
    );
    $baselineNavLabel = $ftsaOnly
        ? (($needsFtsaSnapshot ?? false)
            ? 'SNAPSHOT KEUANGAN'
            : (($needsFtsa ?? false) ? 'FTSA 1–32' : 'SNAPSHOT KEUANGAN'))
        : (($portalOnboardingComplete ?? false)
            ? 'BASELINE DATA'
            : 'BASELINE DATA (WAJIB DI ISI)');
@endphp
@if($showBaselineNav)
    <a href="{{ $baselineNavUrl }}"
       class="flex items-center gap-2 rounded-lg px-3 py-3 {{ $active === 'baseline' ? 'nav-active font-semibold' : 'hover:bg-white/10' }} {{ $baselineNavHighlight ? 'ring-2 ring-gold-400/80 bg-gold-400/10' : '' }}">
        <span class="material-symbols-outlined text-lg opacity-80">fact_check</span>
        <span class="flex-1">{{ $baselineNavLabel }}</span>
        @if($baselineNavHighlight)
            <span class="text-[9px] bg-gold-400 text-navy-900 px-1.5 py-0.5 rounded font-bold animate-pulse">ISI</span>
        @endif
    </a>
@endif
