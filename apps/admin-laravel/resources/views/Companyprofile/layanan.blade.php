@extends('Companyprofile.layouts.main')

@section('title', 'Layanan — YFD Indonesia')

@section('content')

{{-- ============== Hero ============== --}}
<section class="relative h-[480px] md:h-[560px] flex items-center overflow-hidden bg-primary-container">
    <div class="absolute inset-0 opacity-40">
        <img class="w-full h-full object-cover"
             src="https://lh3.googleusercontent.com/aida-public/AB6AXuCCZQUmdNi2jUcE-I8z3JqGj6GKFXsMWzWiO19LbPon1uTC5LJudSTC0kgkY1oPvj-2TUMBozSP1Kg-ROB1WZYNBMuSw1hDUAjzBBPjoOyN48tj_kMJPIe4g0a_TZLwUCML3MPwOSgMrl_-09fw_YB6n5VmyFBW9ZCOue3jJsdp0bQzmBLoSJEzYsbYywzzcIWH9NYKtXhumh_utvQyJ9jWCfGRgFIubNu3jaa5h-fNhiIGvZMkPQqpi6_TOOUuIaT2qJcdPj5Uxzc0"
             alt="Tranquil professional consultation room.">
    </div>
    <div class="relative z-10 px-margin-desktop max-w-container-max mx-auto w-full text-white">
        <div class="max-w-2xl">
            <span class="inline-block px-3 py-1 bg-secondary-container text-on-secondary-container font-label-md text-label-md rounded-full mb-6">EKOSISTEM LAYANAN YFD</span>
            <h1 class="font-display-lg text-display-lg mb-6 leading-tight">Enam Pilar Layanan Kesehatan Finansial</h1>
            <p class="font-body-lg text-body-lg text-on-primary-container opacity-90 mb-8">
                Mengintegrasikan edukasi, proteksi, pendampingan, dan solusi finansial dalam satu ekosistem
                untuk membangun <em>Herd Financial Immunity</em>.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ $primaryCheckupUrl }}" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif class="bg-secondary-container text-on-secondary-container px-8 py-3 rounded-lg font-label-md text-label-md hover:brightness-110 transition-all">
                    Check up sekarang
                </a>
                <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="border border-white/30 bg-white/10 backdrop-blur-sm text-white px-8 py-3 rounded-lg font-label-md text-label-md hover:bg-white/20 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined">chat</span>
                    Konsultasi WA
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ============== Pulse Cards (3 metrics) ============== --}}
<section class="py-16 px-margin-desktop max-w-container-max mx-auto w-full -mt-20 relative z-20">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
        @foreach([
            ['ic' => 'favorite',   'title' => 'Pulse Check',
             'desc' => 'Setiap layanan dimulai dari pemeriksaan kesehatan finansial menyeluruh.'],
            ['ic' => 'medication', 'title' => 'Personalized Plan',
             'desc' => 'Rekomendasi disusun personal sesuai level dan tujuan keuangan masing-masing klien.'],
            ['ic' => 'schedule',   'title' => 'Pendampingan',
             'desc' => 'Bukan sekadar transaksi — tim YFD mendampingi sampai tujuan keuangan tercapai.'],
        ] as $card)
            <div class="bg-surface-container-lowest border border-outline-variant p-8 rounded-xl shadow-sm shadow-primary-container/5">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="font-headline-md text-headline-md text-primary-container">{{ $card['title'] }}</h3>
                    <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">{{ $card['ic'] }}</span>
                </div>
                <p class="font-body-md text-body-md text-on-surface-variant">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ============== Layanan YFD Detail (dynamic from DB) ============== --}}
<section class="py-20 px-margin-desktop max-w-container-max mx-auto w-full space-y-32">

    @forelse($services as $i => $svc)
        @php $no = $i + 1; @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16 items-center" id="layanan-{{ $no }}">
            {{-- Image / Visual side --}}
            <div class="{{ $i % 2 === 0 ? 'order-2 md:order-1' : 'order-2 md:order-2' }}">
                @if($svc->image_url)
                    <div class="relative h-[440px] rounded-2xl overflow-hidden border border-outline-variant shadow-lg">
                        <img class="w-full h-full object-cover" src="{{ $svc->image_url }}" alt="{{ $svc->title }}">
                        <div class="absolute bottom-6 left-6 bg-white/95 backdrop-blur px-6 py-4 rounded-xl shadow-lg border border-outline-variant">
                            <div class="flex items-center gap-2 text-secondary font-bold">
                                <span class="material-symbols-outlined">{{ $svc->icon ?? 'medical_services' }}</span>
                                <span class="font-label-md text-label-md">Layanan #{{ $no }} YFD</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-primary-container text-on-primary rounded-2xl p-12 h-[440px] flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute -bottom-10 -right-10 opacity-10">
                            <span class="material-symbols-outlined text-[200px]">{{ $svc->icon ?? 'medical_services' }}</span>
                        </div>
                        <div class="relative z-10">
                            <span class="material-symbols-outlined text-secondary-fixed text-6xl mb-6">{{ $svc->icon ?? 'medical_services' }}</span>
                            <div class="font-display-lg text-[64px] font-bold leading-none mb-2">{{ str_pad($no, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="font-headline-md text-headline-md opacity-80">{{ $svc->title }}</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Content side --}}
            <div class="{{ $i % 2 === 0 ? 'order-1 md:order-2' : 'order-1 md:order-1' }} space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary-container/10 text-primary-container rounded-lg flex items-center justify-center font-bold">
                        {{ str_pad($no, 2, '0', STR_PAD_LEFT) }}
                    </div>
                    @if($svc->eyebrow)
                        <span class="font-label-md text-label-md text-secondary tracking-widest uppercase">{{ $svc->eyebrow }}</span>
                    @endif
                </div>
                <h2 class="font-headline-lg text-headline-lg text-primary-container">{{ $svc->title }}</h2>
                <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">{{ $svc->description }}</p>

                @php
                    $featureItems = $svc->featureItems();
                    $secondaryCta = $svc->secondaryCta();
                    $primaryCtaUrl = $svc->cta_route === '__primary_checkup__'
                        ? $primaryCheckupUrl
                        : $svc->resolveCtaUrl();
                @endphp

                @if(count($featureItems))
                    <div>
                        <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-3">{{ $svc->featureLabel() }}</p>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($featureItems as $f)
                                <li class="flex items-start gap-3 text-on-surface">
                                    <span class="material-symbols-outlined text-secondary-container mt-0.5" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                    <span class="font-body-md text-body-md">{{ $f }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($footnote = $svc->featureFootnote())
                    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-5 py-4 text-on-surface-variant">
                        @foreach(preg_split("/\r\n|\n|\r/", $footnote) as $line)
                            @if(trim($line) !== '')
                                <p class="font-body-md text-body-md {{ $loop->first ? '' : 'mt-2' }}">{{ trim($line) }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap gap-3 pt-2">
                    @if($svc->cta_label && $primaryCtaUrl)
                        <a href="{{ $primaryCtaUrl }}" @if($svc->cta_route === '__primary_checkup__' && $primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif class="inline-flex items-center gap-2 bg-primary-container text-on-primary px-7 py-3 rounded-lg font-label-md text-label-md hover:opacity-90 transition-all">
                            {{ $svc->cta_label }}
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                    @endif
                    @if($secondaryCta)
                        @php
                            try {
                                $secondaryCtaUrl = !empty($secondaryCta['route']) ? route($secondaryCta['route']) : '#';
                            } catch (\Throwable $e) {
                                $secondaryCtaUrl = '#';
                            }
                        @endphp
                        <a href="{{ $secondaryCtaUrl }}" class="inline-flex items-center gap-2 border border-primary-container text-primary-container px-7 py-3 rounded-lg font-label-md text-label-md hover:bg-primary-container/5 transition-all">
                            {{ $secondaryCta['label'] }}
                        </a>
                    @endif
                    <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 border border-primary-container text-primary-container px-7 py-3 rounded-lg font-label-md text-label-md hover:bg-primary-container/5 transition-all">
                        <span class="material-symbols-outlined">chat</span>
                        Tanya via WA
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-16 text-on-surface-variant italic">Layanan belum tersedia.</div>
    @endforelse

</section>

{{-- ============== Partner Panel ============== --}}
<section class="py-16 px-margin-desktop max-w-container-max mx-auto w-full">
    <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-8 md:p-10">
        <div class="max-w-3xl">
            <span class="font-label-md text-label-md text-secondary tracking-widest uppercase">Kolaborasi</span>
            <h2 class="font-headline-lg text-headline-lg text-primary-container mt-2 mb-3">Partner for Financial Support</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mb-6">
                Konsultasi dapat dilakukan online dan offline. Konsultasi online melalui Zoom, Webex, video call, dan platform serupa.
                Konsultasi offline dapat dijadwalkan jika Anda berdomisili di Bali.
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                ['icon' => 'health_and_safety', 'label' => 'Insurance partner'],
                ['icon' => 'trending_up', 'label' => 'Manager Investasi'],
                ['icon' => 'receipt_long', 'label' => 'Tax analyst'],
                ['icon' => 'favorite', 'label' => 'Wedding organizer'],
                ['icon' => 'home_work', 'label' => 'Property agency'],
            ] as $partner)
                <div class="flex items-center gap-3 rounded-xl border border-outline-variant bg-white px-5 py-4">
                    <span class="material-symbols-outlined text-secondary-container">{{ $partner['icon'] }}</span>
                    <span class="font-body-md text-body-md text-on-surface">{{ $partner['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============== Final CTA ============== --}}
<section class="bg-primary text-on-primary py-24">
    <div class="max-w-container-max mx-auto px-margin-desktop text-center">
        <h2 class="font-display-lg text-display-lg mb-6">Mulai dari Mana Saya Harus Mulai?</h2>
        <p class="font-body-lg text-body-lg mb-10 opacity-80 max-w-2xl mx-auto">
            Tidak yakin layanan mana yang sesuai? Mulai dengan <strong>Financial Health Check Up</strong> —
            kami bantu Anda menemukan area mana yang paling butuh perhatian.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('company.paket') }}" class="bg-secondary-container text-on-secondary-container px-12 py-4 rounded-lg font-label-md text-label-md hover:brightness-105 transition-all shadow-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">monitor_heart</span>
                Mulai Health Check Up
            </a>
            <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="border border-white/30 text-white px-12 py-4 rounded-lg font-label-md text-label-md hover:bg-white/10 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">chat</span>
                Diskusi Gratis di WhatsApp
            </a>
        </div>
    </div>
</section>

@endsection
