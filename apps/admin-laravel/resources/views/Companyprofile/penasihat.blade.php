@extends('Companyprofile.layouts.main')

@section('title', 'Tim Dokter Finansial — YFD')

@section('content')

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

    {{-- ============== Hero ============== --}}
    <header class="mb-16 text-center max-w-3xl mx-auto">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary-container/20 text-secondary text-caption mb-4 border border-secondary/10">
            <span class="material-symbols-outlined text-[16px]">stethoscope</span>
            <span class="font-label-md text-label-md tracking-wider">{{ ($page['page.penasihat.hero_eyebrow'] ?? null) ?: 'TIM DOKTER YFD' }}</span>
        </span>
        <h1 class="font-display-lg text-display-lg text-primary mb-6">{{ ($page['page.penasihat.hero_title'] ?? null) ?: 'Dokter Pertama Untuk Dompet Anda' }}</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">
            {{ ($page['page.penasihat.hero_subtitle'] ?? null) ?: 'YFD didirikan oleh dokter umum yang mempelajari manusia secara utuh — bagaimana stres, trauma, dan emosi memengaruhi pengambilan keputusan, termasuk keputusan finansial.' }}
        </p>
    </header>

    {{-- ============== Tim Dokter (dynamic) ============== --}}
    <section class="mb-20">
        @php
            // Helper to render initials when no photo
            $initials = function (string $name): string {
                $parts = preg_split('/\s+/', preg_replace('/^dr\.\s*/i', '', $name));
                $first = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
                $second = strtoupper(mb_substr($parts[1] ?? '', 0, 1));
                return $first . $second;
            };
            $bgColors = ['bg-primary-container', 'bg-primary', 'bg-secondary-container'];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
            @forelse($advisors as $i => $a)
                <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden flex flex-col hover:shadow-lg transition-shadow group">
                    <div class="h-80 overflow-hidden relative {{ $bgColors[$i % count($bgColors)] }}">
                        @if($a->photo_path)
                            <img src="{{ $a->photo_url }}" alt="{{ $a->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 grid place-items-center">
                                <div class="text-center text-on-primary">
                                    <div class="w-32 h-32 mx-auto rounded-full bg-secondary-container text-on-secondary-container font-display-lg text-[48px] font-bold grid place-items-center mb-4">
                                        {{ $initials($a->name) }}
                                    </div>
                                    @if($a->tag)
                                        <p class="font-caption text-caption opacity-70 tracking-widest">{{ strtoupper($a->tag) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($a->tag)
                            <div class="absolute top-4 right-4 bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-md text-[12px] uppercase tracking-wider shadow-sm">{{ $a->tag }}</div>
                        @endif
                    </div>
                    <div class="p-8 flex-grow">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-headline-lg text-[24px] text-primary-container mb-1">{{ $a->name }}</h3>
                                @if(is_array($a->badges) && count($a->badges))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(array_slice($a->badges, 0, 3) as $b)
                                            <span class="bg-primary-fixed text-on-primary-fixed px-2 py-0.5 rounded text-[12px] font-bold">{{ $b }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @if($a->years_exp)
                                <div class="text-right">
                                    <span class="block font-label-md text-label-md text-secondary">{{ $a->years_exp }}</span>
                                    @if($a->spec_short)
                                        <span class="font-caption text-caption text-on-surface-variant">{{ $a->spec_short }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="border-t border-outline-variant my-4 pt-4">
                            @if($a->role_label)
                                <div class="flex items-center gap-2 mb-3 text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[20px]">{{ $a->spec_icon ?? 'stethoscope' }}</span>
                                    <span class="font-body-md text-body-md font-bold">{{ $a->role_label }}</span>
                                </div>
                            @endif
                            @if($a->spec_long)
                                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">{{ $a->spec_long }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="p-8 pt-0 mt-auto">
                        <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
                           class="block w-full bg-primary-container text-on-primary py-3 rounded font-bold hover:opacity-90 transition-opacity text-center">
                            Konsultasi via WhatsApp
                        </a>
                    </div>
                </div>
            @empty
                <div class="md:col-span-3 text-center py-16 text-on-surface-variant italic">
                    Tim Dokter belum tersedia.
                </div>
            @endforelse
        </div>
    </section>

    {{-- ============== Filosofi Tim ============== --}}
    <section class="mb-20 bg-surface-container-low border border-outline-variant p-12 rounded-2xl">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
            <div class="md:col-span-5">
                <span class="material-symbols-outlined text-secondary text-5xl mb-4">format_quote</span>
                <h2 class="font-headline-lg text-headline-lg text-primary mb-3">
                    Mengapa Dokter Membangun Financial Health?
                </h2>
            </div>
            <div class="md:col-span-7 space-y-4">
                <p class="font-body-lg text-body-lg text-on-surface leading-relaxed">
                    Karena <strong>kesehatan finansial dan kesehatan manusia tidak dapat dipisahkan</strong>.
                    Financial stress dapat menyebabkan gangguan mental, konflik keluarga, burnout, hingga
                    penyakit fisik.
                </p>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Sebaliknya, manusia yang tidak mampu meregulasi emosi lebih rentan terhadap impulsive
                    spending, hutang destruktif, kecanduan validasi sosial, hingga tindakan korupsi.
                </p>
                <p class="font-body-md text-body-md text-primary-container font-bold italic">
                    "Korupsi bukan hanya masalah hukum. Korupsi juga merupakan kegagalan regulasi diri manusia."
                </p>
            </div>
        </div>
    </section>

    {{-- ============== Recruitment / Future Team ============== --}}
    <section class="mb-20">
        <div class="text-center mb-10">
            <span class="font-label-md text-label-md text-secondary tracking-widest block mb-2">RECRUITMENT</span>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Tumbuh Bersama YFD</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                YFD sedang membangun tim Financial Doctor yang lebih luas. Jika Anda dokter, perencana
                keuangan QWP/CFP/RFP, edukator, atau profesional finansial yang share visi kami — mari berkolaborasi.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
            @foreach([
                ['ic' => 'health_and_safety',  'title' => 'Dokter / Tenaga Kesehatan',  'desc' => 'Dokter umum atau spesialis dengan minat di financial wellness dan emotional finance.'],
                ['ic' => 'badge',              'title' => 'Certified Planner',          'desc' => 'QWP, CFP, RFP, atau sertifikasi finansial lain yang relevan dengan misi YFD.'],
                ['ic' => 'co_present',         'title' => 'Educator & Content',         'desc' => 'Edukator finansial untuk mengisi platform Wealthpedia, webinar, dan workshop.'],
            ] as $role)
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 border-dashed hover:border-primary-container transition-colors">
                    <div class="w-12 h-12 bg-primary-container/10 rounded-lg flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary-container">{{ $role['ic'] }}</span>
                    </div>
                    <h3 class="font-headline-md text-[18px] font-bold text-primary mb-2">{{ $role['title'] }}</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant mb-4">{{ $role['desc'] }}</p>
                    <a href="mailto:{{ $yfd['email'] }}?subject=Recruitment YFD — {{ $role['title'] }}"
                       class="inline-flex items-center gap-2 text-primary-container font-label-md text-label-md hover:underline">
                        Hubungi via Email <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============== Partner for Financial Support ============== --}}
    <section class="mb-20">
        <div class="text-center mb-10">
            <span class="font-label-md text-label-md text-secondary tracking-widest block mb-2">KOLABORASI</span>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Partner for Financial Support</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                YFD berkolaborasi dengan mitra profesional untuk mendukung kebutuhan finansial klien secara menyeluruh —
                dari proteksi, investasi, perpajakan, hingga perencanaan hidup.
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-gutter">
            @forelse($partners ?? [] as $partner)
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 hover:border-primary-container hover:shadow-md transition-all">
                    <div class="w-12 h-12 bg-secondary-container/20 rounded-lg flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary-container">{{ $partner->icon }}</span>
                    </div>
                    <h3 class="font-headline-md text-[18px] font-bold text-primary mb-2">{{ $partner->title }}</h3>
                    @if($partner->description)
                        <p class="font-body-md text-body-md text-on-surface-variant mb-4">{{ $partner->description }}</p>
                    @endif
                    <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 text-primary-container font-label-md text-label-md hover:underline">
                        Tanya via WA <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>
            @empty
                <p class="font-body-md text-on-surface-variant col-span-full text-center">Daftar mitra sedang diperbarui.</p>
            @endforelse
        </div>
    </section>

    {{-- ============== Pulse CTA ============== --}}
    <div class="bg-primary-container text-on-primary rounded-xl p-10 shadow-xl relative overflow-hidden">
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center justify-end pr-4 md:pr-10 opacity-10">
            <span class="material-symbols-outlined text-[240px] leading-none select-none">ecg_heart</span>
        </div>
        <div class="relative z-10 lg:w-2/3">
            <h2 class="font-headline-lg text-headline-lg mb-4">Siap Bertemu Dokter Finansial?</h2>
            <p class="font-body-lg text-body-lg mb-8 opacity-90">
                Mulai dengan <strong>Financial Health Check Up</strong>, atau langsung diskusi gratis di
                WhatsApp. Tim YFD siap mendampingi Anda dari level dasar hingga advance.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $primaryCheckupUrl }}" @if($primaryCheckupNewTab) target="_blank" rel="noopener noreferrer" @endif class="inline-block bg-secondary-container text-on-secondary-container px-8 py-3 rounded font-bold shadow-lg hover:scale-105 transition-transform">
                    Mulai Screening Gratis
                </a>
                <a href="{{ route('company.paket') }}" class="inline-block border border-white/30 text-white px-8 py-3 rounded font-bold hover:bg-white/10 transition-colors">
                    Lihat Tarif Konsultasi
                </a>
                <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 border border-white/30 text-white px-8 py-3 rounded hover:bg-white/10 transition-colors">
                    <span class="material-symbols-outlined">chat</span> Konsultasi Gratis
                </a>
            </div>
        </div>
    </div>
</main>

@endsection
