@extends('Companyprofile.layouts.main')

@section('title', ($bundle['title'] ?? 'Layanan') . ' — YFD')

@section('content')

@php
    $b = $bundle;
    $fmt = fn (?int $n) => $n !== null ? 'Rp ' . number_format($n, 0, ',', '.') : 'Sesuai kebutuhan';
@endphp

<main class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12 md:py-16">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-on-surface-variant mb-8">
        <a href="{{ route('company.layanan') }}" class="hover:text-primary-container">Layanan</a>
        <span class="mx-2">/</span>
        <span class="text-primary font-medium">{{ $b['title'] }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-start mb-16">
        {{-- Visual --}}
        <div class="bg-primary-container text-on-primary rounded-2xl p-10 md:p-12 min-h-[360px] flex flex-col justify-center relative overflow-hidden order-2 lg:order-1">
            <div class="absolute -bottom-10 -right-10 opacity-10">
                <span class="material-symbols-outlined text-[200px]">{{ $b['icon'] ?? 'medical_services' }}</span>
            </div>
            <div class="relative z-10">
                <span class="font-label-md text-label-md text-secondary-fixed tracking-widest uppercase">{{ $b['eyebrow'] ?? '' }}</span>
                <span class="material-symbols-outlined text-secondary-fixed text-6xl my-6 block">{{ $b['icon'] ?? 'medical_services' }}</span>
                <div class="font-display text-[72px] font-bold leading-none mb-2">{{ $b['number'] ?? '00' }}</div>
                <div class="font-headline-lg text-headline-lg opacity-90">{{ $b['title'] }}</div>
            </div>
        </div>

        {{-- Content --}}
        <div class="space-y-6 order-1 lg:order-2">
            <h1 class="font-display-lg text-display-lg text-primary">{{ $b['title'] }}</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">{{ $b['description'] }}</p>

            @if(!empty($b['features']))
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-3">{{ $b['features_label'] ?? 'Cakupan' }}</p>
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($b['features'] as $f)
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-secondary-container mt-0.5" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                <span class="font-body-md text-body-md text-on-surface">{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($b['pricing']))
                <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5 space-y-3">
                    <p class="font-label-md text-label-md text-primary font-semibold">Tarif</p>
                    @foreach($b['pricing'] as $row)
                        <div class="flex flex-wrap justify-between gap-2 border-b border-outline-variant/60 pb-3 last:border-0 last:pb-0">
                            <div>
                                <div class="font-body-md font-semibold text-on-surface">{{ $row['label'] }}</div>
                                @if(!empty($row['note']))
                                    <div class="text-sm text-on-surface-variant">{{ $row['note'] }}</div>
                                @endif
                            </div>
                            <div class="font-bold text-primary-container whitespace-nowrap">{{ $fmt($row['amount'] ?? null) }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($b['footnote']))
                <p class="text-sm text-on-surface-variant italic">{{ $b['footnote'] }}</p>
            @endif

            <div class="flex flex-wrap gap-3 pt-2">
                @if(!empty($b['cta_primary']))
                    @php $cta = $b['cta_primary']; @endphp
                    @if(($cta['type'] ?? '') === 'wa')
                        <button type="button" onclick="openBundleWa('{{ $cta['wa_topic'] ?? $b['title'] }}')"
                                class="inline-flex items-center gap-2 bg-[#25D366] text-white px-7 py-3 rounded-lg font-label-md text-label-md hover:opacity-90 transition-all shadow-md">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">chat</span>
                            {{ $cta['label'] }}
                        </button>
                    @elseif(!empty($cta['route']))
                        <a href="{{ route($cta['route']) }}" class="inline-flex items-center gap-2 bg-primary-container text-on-primary px-7 py-3 rounded-lg font-label-md text-label-md hover:opacity-90 transition-all">
                            {{ $cta['label'] }}
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                    @endif
                @endif
                @if(!empty($b['cta_secondary']))
                    @php $cta2 = $b['cta_secondary']; @endphp
                    @if(($cta2['type'] ?? '') === 'wa')
                        <button type="button" onclick="openBundleWa('{{ $cta2['wa_topic'] ?? $b['title'] }}')"
                                class="inline-flex items-center gap-2 border border-primary-container text-primary-container px-7 py-3 rounded-lg font-label-md text-label-md hover:bg-primary-container/5 transition-all">
                            <span class="material-symbols-outlined">chat</span>
                            {{ $cta2['label'] }}
                        </button>
                    @elseif(!empty($cta2['route']))
                        <a href="{{ route($cta2['route']) }}" class="inline-flex items-center gap-2 border border-primary-container text-primary-container px-7 py-3 rounded-lg font-label-md text-label-md hover:bg-primary-container/5 transition-all">
                            {{ $cta2['label'] }}
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Education offerings grid --}}
    @if(!empty($b['offerings']))
        <section class="mb-16">
            <h2 class="font-headline-lg text-headline-lg text-primary mb-8 text-center">Produk Edukasi</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
                @foreach($b['offerings'] as $item)
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 flex flex-col hover:border-primary-container/40 transition-colors">
                        <span class="material-symbols-outlined text-primary-container text-[36px] mb-4">{{ $item['icon'] }}</span>
                        <h3 class="font-headline-md text-[18px] font-bold text-primary mb-2">{{ $item['title'] }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant flex-grow mb-4">{{ $item['desc'] }}</p>
                        @if(($item['status'] ?? '') === 'free' && !empty($item['route']))
                            <a href="{{ route($item['route']) }}" class="text-sm font-semibold text-primary-container hover:underline">Buka gratis →</a>
                        @elseif(($item['status'] ?? '') === 'available')
                            <button type="button" onclick="openBundleWa('{{ $item['title'] }} — Financial Education Platform')"
                                    class="text-sm font-semibold text-primary-container hover:underline text-left">
                                Info &amp; pendaftaran →
                            </button>
                        @else
                            <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant bg-surface-container-high px-2 py-1 rounded w-fit">Segera hadir</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Back to layanan --}}
    <div class="text-center">
        <a href="{{ route('company.layanan') }}" class="inline-flex items-center gap-2 text-primary-container font-semibold hover:underline">
            <span class="material-symbols-outlined">arrow_back</span>
            Lihat semua layanan YFD
        </a>
    </div>
</main>

@push('scripts')
<script>
function openBundleWa(topic) {
    const lines = [
        'Halo Tim YFD, saya tertarik dengan layanan:',
        '',
        '*' + topic + '*',
        '',
        'Mohon info paket, jadwal, dan biaya. Terima kasih.'
    ];
    const url = 'https://wa.me/{{ $yfd['wa_number'] }}?text=' + encodeURIComponent(lines.join('\n'));
    window.open(url, '_blank', 'noopener');
}
</script>
@endpush

@endsection
