@extends('Companyprofile.layouts.main')

@section('title', 'Tarif Konsultasi Finansial — YFD')

@section('content')

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

    {{-- ============== Hero: Screening GRATIS ============== --}}
    <header class="text-center mb-16">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary-container/20 text-secondary text-caption mb-4 border border-secondary/10">
            <span class="material-symbols-outlined text-[16px]">monitor_heart</span>
            <span class="font-label-md text-label-md tracking-wider">FINANCIAL HEALTH CHECK UP</span>
        </span>
        <h1 class="font-display-lg text-display-lg text-primary mb-4">Screening Gratis Dulu — Konsultasi Setelahnya</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-3xl mx-auto">
            <strong>Financial Health Check-Up / screening</strong> adalah layanan <strong>gratis</strong> (3–5 menit)
            untuk mengetahui tahap finansial Anda. Biaya konsultasi dengan tim dokter YFD baru berlaku
            <strong>setelah screening</strong>, disesuaikan dengan tahap finansial Anda.
        </p>
        <div class="mt-8">
            <a href="{{ $primaryCheckupUrl }}" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif
               class="inline-flex items-center gap-2 btn btn-gold btn-lg">
                <span class="material-symbols-outlined text-[20px]">play_arrow</span>
                Mulai Screening Gratis
            </a>
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
            <h2 class="font-headline-lg text-headline-lg mb-3">Yang Gratis vs Yang Berbayar</h2>
            <p class="font-body-lg text-body-lg opacity-90 mb-4">
                <strong>Gratis:</strong> kuesioner screening, skor tahap finansial (Surviving / Growing / Steady / Comfortable),
                dan gambaran risiko awal.
            </p>
            <p class="font-body-lg text-body-lg opacity-90">
                <strong>Berbayar:</strong> sesi konsultasi 1-on-1 dengan dokter finansial YFD via WhatsApp —
                tarif per sesi mengikuti tahap finansial hasil screening Anda.
            </p>
        </div>
    </section>

    {{-- ============== Tarif Konsultasi per Tahap ============== --}}
    <section class="mb-20">
        <div class="text-center mb-12">
            <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Tarif Konsultasi per Tahap Finansial</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                Setelah screening, sistem menampilkan estimasi tarif sesuai tahap Anda.
                Konsultasi awal standar mulai <strong>{{ \App\Support\ConsultationPricing::formatRupiah($consultationMeta['standard_from'] ?? 100000) }}</strong>/sesi;
                program Recovery mulai <strong>{{ \App\Support\ConsultationPricing::formatRupiah($consultationMeta['recovery_from'] ?? 150000) }}</strong>/sesi.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
            @foreach($consultationTiers as $stageKey => $tier)
                <div class="bg-surface-container-lowest border-2 border-outline-variant rounded-2xl p-6 flex flex-col hover:border-primary-container/40 transition-all">
                    <div class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-1">{{ $tier['phase'] ?? '' }}</div>
                    <h3 class="font-headline-lg text-[22px] font-bold text-primary mb-2">{{ $tier['label'] }}</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant min-h-[72px] mb-4">{{ $tier['description'] ?? '' }}</p>

                    <div class="text-center mb-5 pb-5 border-b border-outline-variant mt-auto">
                        <div class="font-display-lg text-[28px] font-bold text-primary-container leading-tight">
                            {{ \App\Support\ConsultationPricing::formatRange($tier) }}
                        </div>
                        <div class="font-caption text-caption text-on-surface-variant mt-1">{{ $consultationMeta['period'] ?? '/sesi' }}</div>
                    </div>

                    <a href="{{ \App\Support\ConsultationPricing::bookingUrl($stageKey) }}"
                       class="block text-center py-3 rounded-lg font-label-md text-label-md border-2 border-primary-container text-primary-container hover:bg-primary-container/5 transition-all">
                        Booking Konsultasi
                    </a>
                </div>
            @endforeach
        </div>

        <p class="text-center text-sm text-on-surface-variant mt-8 max-w-2xl mx-auto">
            {{ $consultationMeta['multi_session_note'] ?? '' }}
        </p>
    </section>

    {{-- ============== Apa yang Diukur (Screening) ============== --}}
    <section class="mb-20">
        <div class="text-center mb-12">
            <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Apa Saja yang Diperiksa di Screening?</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                Seperti medical check-up — kami periksa aspek vital kesehatan finansial Anda (gratis).
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
        <h2 class="font-headline-lg text-headline-lg mb-4">Belum Tahu Tahap Finansial Anda?</h2>
        <p class="font-body-lg text-body-lg opacity-80 mb-8 max-w-2xl mx-auto">
            Mulai screening gratis dulu — hasilnya langsung menunjukkan tahap finansial dan estimasi tarif konsultasi.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ $primaryCheckupUrl }}" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif
               class="inline-flex items-center gap-2 bg-secondary-container text-on-secondary-container px-10 py-4 rounded-lg font-label-md text-label-md hover:brightness-105 shadow-lg transition-all">
                <span class="material-symbols-outlined">monitor_heart</span>
                Mulai Screening Gratis
            </a>
            <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 bg-[#25D366] text-white px-10 py-4 rounded-lg font-label-md text-label-md hover:opacity-90 shadow-lg transition-all">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">chat</span>
                Tanya Tim YFD via WhatsApp
            </a>
        </div>
    </section>

</main>
@endsection
