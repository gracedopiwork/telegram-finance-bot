@extends('Companyprofile.layouts.main')

@section('title', 'Tentang YFD — Indonesia\'s First Financial Health Center')

@section('content')

{{-- ============== Hero ============== --}}
<section class="relative bg-white py-24 overflow-hidden">
    <div class="max-w-container-max mx-auto px-margin-desktop flex flex-col md:flex-row items-center gap-16 relative z-10">
        <div class="w-full md:w-1/2">
            <span class="text-secondary font-label-md text-label-md tracking-widest block mb-4">TENTANG YFD</span>
            <h1 class="text-display-lg font-display-lg text-primary mb-6 leading-tight">
                Pusat Kesehatan Finansial Pertama di Indonesia.
            </h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant mb-8 max-w-xl">
                YFD didirikan oleh dua dokter umum yang melihat bahwa masyarakat tidak hanya butuh
                kesehatan jasmani, tetapi juga kesehatan finansial yang krusial dalam mendukung
                stabilitas dan kelangsungan hidup bernegara.
            </p>
            <p class="font-body-md text-body-md text-on-surface-variant italic">
                "Tidak hanya tubuh yang bisa diserang penyakit, namun dompet yang sakit juga memerlukan dokter."
            </p>
        </div>
        <div class="w-full md:w-1/2 relative">
            <div class="aspect-square bg-surface-container-high rounded-full overflow-hidden border-8 border-white clinical-shadow">
                <img class="w-full h-full object-cover"
                     src="https://lh3.googleusercontent.com/aida-public/AB6AXuBYsEYP9JAQ0UhRxYYzXM2GMO8PHsKCNzct-usPGouh1p-swYf1nIYRVwLiUv7oXq1dFCpNzIxXugdDOuCn8GLUcEdQcQebx28Lt4uhcF1DChI5XuQEIH7Kl95dAaTjUZEP2xaiAynL2Kh-gY8F7SDcicagZvNPxBvZMWSXjOod6QVygzEXCIJ4YIIfGpTR7-1UiKZ0WqmL1169dRBBN3w4nwbkdZ-eIkOqDp0bX6vdzeqNFQDp_zdhvDxJPDnSD6dk-lUuMLYK0js6"
                     alt="YFD doctor in clinical setting.">
            </div>
            <div class="absolute -bottom-6 -left-6 bg-white p-6 clinical-shadow max-w-[260px]">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-secondary text-[32px]">groups</span>
                    <span class="text-primary font-bold font-headline-md text-headline-md">Herd Financial Immunity</span>
                </div>
                <p class="font-caption text-caption text-on-surface-variant">
                    Membangun kekebalan komunitas finansial untuk masyarakat mayoritas Indonesia.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============== Latar Belakang ============== --}}
<section class="bg-surface-container-low py-24">
    <div class="max-w-container-max mx-auto px-margin-desktop">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12">
            <div class="md:col-span-4">
                <h2 class="font-headline-lg text-headline-lg text-primary gold-border pl-6">
                    Latar Belakang Berdirinya YFD Indonesia
                </h2>
            </div>
            <div class="md:col-span-8 space-y-6">
                @if(!empty($about['about.bg_p1']))
                    <p class="font-body-lg text-body-lg text-on-surface leading-relaxed">{!! nl2br(e($about['about.bg_p1'])) !!}</p>
                @endif
                @if(!empty($about['about.bg_p2']))
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">{!! nl2br(e($about['about.bg_p2'])) !!}</p>
                @endif
                @if(!empty($about['about.bg_p3']))
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">{!! nl2br(e($about['about.bg_p3'])) !!}</p>
                @endif
                <div class="grid grid-cols-2 gap-8 pt-8 border-t border-outline-variant">
                    <div>
                        <div class="font-display-lg text-display-lg text-primary">2 Dokter</div>
                        <p class="font-label-md text-label-md text-secondary">Founder Dokter Umum</p>
                    </div>
                    <div>
                        <div class="font-display-lg text-display-lg text-primary">2035</div>
                        <p class="font-label-md text-label-md text-secondary">Target Visi YFD</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============== Visi & Misi ============== --}}
<section class="bg-white py-24">
    <div class="max-w-container-max mx-auto px-margin-desktop">

        {{-- Visi --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 mb-20">
            <div class="md:col-span-3">
                <span class="font-label-md text-label-md text-secondary tracking-widest block mb-2">VISI</span>
                <h2 class="font-headline-lg text-headline-lg text-primary">Visi Kami</h2>
            </div>
            <div class="md:col-span-9">
                <div class="bg-primary-container/5 border-l-4 border-secondary-container p-8 rounded-r-xl">
                    <p class="font-body-lg text-body-lg text-on-surface leading-relaxed">
                        {!! nl2br(e($vision['vision.text'] ?? 'Menjadi pelopor dan penggerak pusat kesehatan finansial pertama di Indonesia.')) !!}
                    </p>
                </div>
            </div>
        </div>

        {{-- Misi 8 poin --}}
        <div>
            <div class="text-center mb-12">
                <span class="font-label-md text-label-md text-secondary tracking-widest block mb-2">MISI</span>
                <h2 class="font-headline-lg text-headline-lg text-primary mb-4">Delapan Misi YFD</h2>
                <div class="w-24 h-1 bg-secondary-container mx-auto"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
                @for($n = 1; $n <= 8; $n++)
                    @php
                        $title = $mission["mission.m{$n}.title"] ?? null;
                        $icon  = $mission["mission.m{$n}.icon"]  ?? 'check';
                        $desc  = $mission["mission.m{$n}.desc"]  ?? null;
                    @endphp
                    @if($title)
                        <div class="flex gap-5 p-6 border border-outline-variant rounded-xl bg-surface-container-lowest hover:border-primary-container hover:shadow-md transition-all">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-primary-container text-on-primary rounded-lg flex items-center justify-center font-bold relative">
                                    <span class="material-symbols-outlined">{{ $icon }}</span>
                                    <span class="absolute -top-2 -right-2 w-6 h-6 bg-secondary-container text-on-secondary-container text-[11px] font-bold rounded-full flex items-center justify-center">{{ $n }}</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-headline-md text-[18px] font-bold text-primary mb-1">{{ $title }}</h3>
                                <p class="font-body-md text-body-md text-on-surface-variant">{{ $desc }}</p>
                            </div>
                        </div>
                    @endif
                @endfor
            </div>
        </div>
    </div>
</section>

{{-- ============== 6 Core Values ============== --}}
<section class="bg-surface-container-highest/30 py-24">
    <div class="max-w-container-max mx-auto px-margin-desktop">
        <div class="text-center mb-16">
            <span class="font-label-md text-label-md text-secondary tracking-widest block mb-3">CORE VALUES</span>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-4">Enam Nilai Inti YFD</h2>
            <div class="w-24 h-1 bg-secondary-container mx-auto"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
            @for($n = 1; $n <= 6; $n++)
                @php
                    $title = $values["values.v{$n}.title"] ?? null;
                    $icon  = $values["values.v{$n}.icon"]  ?? 'favorite';
                    $desc  = $values["values.v{$n}.desc"]  ?? null;
                @endphp
                @if($title)
                    <div class="p-8 border border-outline-variant hover:border-primary transition-all duration-300 clinical-shadow bg-white group">
                        <div class="w-16 h-16 bg-primary-container flex items-center justify-center rounded-lg mb-6 group-hover:bg-primary transition-colors">
                            <span class="material-symbols-outlined text-white text-[32px]">{{ $icon }}</span>
                        </div>
                        <h3 class="font-headline-md text-headline-md text-primary mb-3">{{ $title }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">{{ $desc }}</p>
                    </div>
                @endif
            @endfor
        </div>
    </div>
</section>

{{-- ============== Founder Story ============== --}}
<section class="bg-primary-container text-on-primary py-24">
    <div class="max-w-container-max mx-auto px-margin-desktop">
        <div class="text-center mb-16">
            <span class="font-label-md text-label-md text-secondary-fixed tracking-widest block mb-3">FOUNDER STORY</span>
            <h2 class="font-headline-lg text-headline-lg mb-4">Mengapa Dokter Membangun Financial Health?</h2>
            <p class="font-body-lg text-body-lg opacity-80 max-w-2xl mx-auto">
                Karena kesehatan finansial dan kesehatan manusia tidak dapat dipisahkan.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-8 backdrop-blur-sm">
                <div class="text-secondary-fixed font-label-md text-label-md mb-3">2016 — 2018</div>
                <h3 class="font-headline-md text-headline-md mb-4">Akar Cerita</h3>
                <p class="font-body-md text-body-md opacity-90 leading-relaxed">
                    YFD didirikan oleh dokter umum yang memiliki ketertarikan mendalam pada dunia finansial
                    sejak <strong>2018</strong>. Tapi perjalanan ini tidak lahir dari privilese — YFD lahir
                    dari pengalaman hidup yang penuh tekanan finansial sejak kecil.
                </p>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-2xl p-8 backdrop-blur-sm">
                <div class="text-secondary-fixed font-label-md text-label-md mb-3">REALISASI</div>
                <h3 class="font-headline-md text-headline-md mb-4">Trauma Finansial</h3>
                <p class="font-body-md text-body-md opacity-90 leading-relaxed">
                    Kemiskinan dan ketidakstabilan finansial bukan hanya menciptakan kekurangan uang —
                    tetapi juga menciptakan <strong>trauma</strong>. Trauma ini memengaruhi cara mengambil
                    keputusan, memandang diri, hingga kemampuan membangun masa depan.
                </p>
            </div>

            <div class="bg-secondary-container/20 border border-secondary-container/40 rounded-2xl p-8 backdrop-blur-sm">
                <div class="text-secondary-fixed font-label-md text-label-md mb-3">FILOSOFI YFD</div>
                <h3 class="font-headline-md text-headline-md mb-4">Akar Masalah Finansial</h3>
                <p class="font-body-md text-body-md opacity-90 leading-relaxed">
                    Dunia finansial selama ini terlalu fokus pada angka. Padahal akar masalah finansial
                    manusia sering kali berada pada <strong>emosi, perilaku, trauma, rasa takut</strong>,
                    dan ketidakmampuan meregulasi diri.
                </p>
            </div>
        </div>

        <div class="mt-16 max-w-3xl mx-auto text-center">
            <span class="material-symbols-outlined text-secondary-fixed text-5xl mb-4">format_quote</span>
            <p class="font-headline-lg text-headline-lg italic mb-6">
                "Membangun sistem finansial yang sehat dimulai dari membangun manusia yang sehat."
            </p>
            <p class="font-body-md text-body-md opacity-80">— Filosofi Inti YFD</p>
        </div>
    </div>
</section>

{{-- ============== Korupsi & Generasi Bagian ============== --}}
<section class="bg-white py-24">
    <div class="max-w-container-max mx-auto px-margin-desktop">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
            <div>
                <span class="font-label-md text-label-md text-secondary tracking-widest block mb-3">VISI MAKRO</span>
                <h2 class="font-headline-lg text-headline-lg text-primary mb-6">
                    Korupsi Bukan Hanya Masalah Hukum.
                </h2>
                <p class="font-body-lg text-body-lg text-on-surface-variant mb-4">
                    Korupsi juga merupakan <strong>kegagalan regulasi diri manusia</strong>. Bangsa yang sehat
                    secara finansial tidak hanya dibangun dengan sistem ekonomi yang kuat — tetapi juga
                    dengan manusia yang punya:
                </p>
                <ul class="space-y-3 mb-6">
                    @foreach([
                        'Emotional resilience',
                        'Self-awareness',
                        'Delayed gratification',
                        'Integritas',
                        'Kemampuan mengambil keputusan sehat di bawah tekanan',
                    ] as $trait)
                        <li class="flex items-center gap-3 font-body-md text-body-md text-on-surface">
                            <span class="material-symbols-outlined text-secondary">check_circle</span>
                            {{ $trait }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-surface-container-low border border-outline-variant rounded-2xl p-10">
                <div class="bg-error-container/30 text-on-error-container p-3 rounded-lg w-fit mb-4">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-primary mb-4">Jika Tidak Diintervensi</h3>
                <p class="font-body-md text-body-md text-on-surface-variant mb-6">
                    Jika generasi muda Indonesia dibangun di atas trauma yang tidak selesai, financial
                    anxiety, impulsivitas, dan pondasi finansial yang rapuh — bonus demografi dapat berubah
                    menjadi <strong>krisis sosial besar</strong>.
                </p>
                <div class="bg-secondary-container/20 border-l-4 border-secondary-container p-4 rounded-r">
                    <p class="font-body-md text-body-md text-primary">
                        Namun jika Indonesia berhasil membangun generasi yang resilient, sehat secara
                        emosional & finansial, dan mampu mengambil keputusan dengan baik — Indonesia dapat
                        membangun masa depan yang jauh lebih kuat.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============== Final CTA ============== --}}
<section class="py-24 bg-primary text-on-primary">
    <div class="max-w-container-max mx-auto px-margin-desktop text-center">
        <h2 class="font-display-lg text-display-lg mb-6">Building Financially Healthy Generations.</h2>
        <p class="font-body-lg text-body-lg mb-10 opacity-80 max-w-2xl mx-auto">
            Mulai perjalanan kesehatan finansial Anda hari ini bersama tim dokter YFD.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-6">
            <a href="{{ route('company.paket') }}"
               class="bg-secondary-container text-on-secondary-container font-label-md text-label-md px-10 py-5 hover:scale-105 transition-transform font-bold">
                Mulai Health Check Up
            </a>
            <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
               class="border border-white/30 text-white font-label-md text-label-md px-10 py-5 hover:bg-white/10 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">chat</span>
                Konsultasi via WhatsApp
            </a>
        </div>
    </div>
</section>

@endsection
