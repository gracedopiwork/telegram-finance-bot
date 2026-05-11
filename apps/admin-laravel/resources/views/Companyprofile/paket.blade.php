@extends('Companyprofile.layouts.main')

@section('title', 'Paket Financial Health Check Up — YFD')

@section('content')

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

    {{-- ============== Hero ============== --}}
    <header class="text-center mb-16">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary-container/20 text-secondary text-caption mb-4 border border-secondary/10">
            <span class="material-symbols-outlined text-[16px]">monitor_heart</span>
            <span class="font-label-md text-label-md tracking-wider">FINANCIAL HEALTH CHECK UP</span>
        </span>
        <h1 class="font-display-lg text-display-lg text-primary mb-4">Mulai dari Sini: Diagnosa Kesehatan Finansial Anda</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-3xl mx-auto">
            Layanan pemeriksaan kesehatan finansial untuk mengukur cashflow, debt ratio, dana darurat,
            proteksi, investasi, perilaku finansial, financial stress, dan tingkat risiko Anda.
        </p>
        <div class="flex justify-center flex-wrap gap-2 mt-6">
            <span class="bg-primary-fixed text-on-primary-fixed px-3 py-1 rounded font-label-md text-label-md">Financial Health Score</span>
            <span class="bg-primary-fixed text-on-primary-fixed px-3 py-1 rounded font-label-md text-label-md">Financial Risk Category</span>
            <span class="bg-primary-fixed text-on-primary-fixed px-3 py-1 rounded font-label-md text-label-md">Personalized Recommendation</span>
        </div>
    </header>

    {{-- ============== Pulse Highlight ============== --}}
    <section class="mb-20 bg-primary-container text-on-primary p-10 rounded-2xl flex flex-col md:flex-row items-center gap-8">
        <div class="flex-shrink-0">
            <div class="w-32 h-32 rounded-full border-4 border-secondary-container/40 grid place-items-center">
                <span class="material-symbols-outlined text-secondary-fixed text-[64px]" style="font-variation-settings: 'FILL' 1;">ecg_heart</span>
            </div>
        </div>
        <div>
            <h2 class="font-headline-lg text-headline-lg mb-3">Pulse Metric: Vital Signs Anda</h2>
            <p class="font-body-lg text-body-lg opacity-90">
                Setiap paket menghasilkan <strong>Financial Health Score</strong> yang mudah dipahami —
                seperti tekanan darah dan denyut jantung. Anda akan tahu persis area mana yang sehat dan
                area mana yang butuh perawatan.
            </p>
        </div>
    </section>

    {{-- ============== Packages ============== --}}
    <section class="grid grid-cols-1 md:grid-cols-3 gap-gutter mb-20">
        @forelse($packages as $pkg)
            @php $isFeatured = $pkg->is_recommended || $pkg->variant === 'featured'; @endphp
            <div class="relative bg-surface-container-lowest border-2 rounded-2xl p-8 flex flex-col
                {{ $isFeatured ? 'border-primary-container shadow-2xl scale-[1.02]' : 'border-outline-variant hover:border-primary-container/40 transition-all' }}">
                @if($isFeatured)
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary-container text-on-secondary-container px-4 py-1 rounded-full font-label-md text-label-md uppercase tracking-wider shadow-md">
                        Paling Populer
                    </div>
                @endif

                <div class="text-center mb-6">
                    <h3 class="font-headline-lg text-[24px] font-bold text-primary mb-2">{{ $pkg->name }}</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant min-h-[48px]">{{ $pkg->description }}</p>
                </div>

                <div class="text-center mb-6 pb-6 border-b border-outline-variant">
                    <div class="font-display-lg text-[36px] font-bold text-primary-container">
                        Rp {{ number_format($pkg->price, 0, ',', '.') }}
                    </div>
                    <div class="font-caption text-caption text-on-surface-variant mt-1">{{ $pkg->period ?? '/paket' }}</div>
                </div>

                @if(is_array($pkg->features) && count($pkg->features))
                    <ul class="space-y-3 mb-6 flex-grow">
                        @foreach($pkg->features as $f)
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-secondary mt-0.5" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                <span class="font-body-md text-body-md text-on-surface">{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <a href="{{ route('company.pertemuan', ['plan' => $pkg->code]) }}"
                   class="block text-center py-3 rounded-lg font-label-md text-label-md transition-all
                       {{ $isFeatured
                            ? 'bg-primary-container text-on-primary hover:opacity-90'
                            : 'border-2 border-primary-container text-primary-container hover:bg-primary-container/5' }}">
                    Pilih Paket Ini
                </a>
            </div>
        @empty
            <div class="md:col-span-3 text-center py-12 text-on-surface-variant italic">
                Paket belum tersedia. Hubungi tim YFD via WhatsApp untuk info lebih lanjut.
            </div>
        @endforelse
    </section>

    {{-- ============== Apa yang Diukur ============== --}}
    <section class="mb-20">
        <div class="text-center mb-12">
            <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Apa Saja yang Diperiksa?</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                Seperti medical check up — kami periksa semua aspek vital kesehatan finansial Anda.
            </p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-gutter">
            @foreach([
                ['ic' => 'water_drop',      'label' => 'Cashflow'],
                ['ic' => 'credit_card_off', 'label' => 'Debt Ratio'],
                ['ic' => 'savings',         'label' => 'Dana Darurat'],
                ['ic' => 'shield',          'label' => 'Proteksi'],
                ['ic' => 'trending_up',     'label' => 'Investasi'],
                ['ic' => 'psychology',      'label' => 'Perilaku Finansial'],
                ['ic' => 'mood_bad',        'label' => 'Financial Stress'],
                ['ic' => 'warning',         'label' => 'Risk Level'],
            ] as $item)
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 text-center hover:border-primary-container transition-colors">
                    <div class="w-14 h-14 mx-auto mb-3 bg-primary-container/10 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary-container">{{ $item['ic'] }}</span>
                    </div>
                    <p class="font-label-md text-label-md text-primary">{{ $item['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============== CTA ============== --}}
    <section class="bg-primary text-on-primary rounded-2xl p-12 text-center">
        <h2 class="font-headline-lg text-headline-lg mb-4">Belum Yakin Pilih Paket Mana?</h2>
        <p class="font-body-lg text-body-lg opacity-80 mb-8 max-w-2xl mx-auto">
            Konsultasi gratis di WhatsApp dulu — tim YFD bantu Anda menemukan paket yang paling sesuai
            dengan kondisi finansial saat ini.
        </p>
        <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-[#25D366] text-white px-10 py-4 rounded-lg font-label-md text-label-md hover:opacity-90 shadow-lg transition-all">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">chat</span>
            Tanya Tim YFD via WhatsApp
        </a>
    </section>

</main>
@endsection
