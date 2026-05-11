@extends('Companyprofile.layouts.main')

@section('title', 'Informasi & Kontak — YFD')

@section('content')

{{-- ============== Hero ============== --}}
<section class="relative h-[400px] overflow-hidden bg-primary-container">
    <div class="absolute inset-0 opacity-30">
        <img class="w-full h-full object-cover"
             src="https://lh3.googleusercontent.com/aida-public/AB6AXuC_5nvVve-ESQ2KsXxHTFpZGlbKwwfky3a34CKEtZWbNOiLj2TmsCFcSuYhWuJdw0quPCtVVXu-sRqXkNasPMsYUKQfN8thgMmJxNmaK1MTjvj5rnbNo7qQqA9qVSHva9mpPn6Z8Q5r2BULUxR4BCKQA-ZLbbVOQPY3WGAz3HB7NQRjgA9vMynR3qQJTbs01q8dLVQ_xj57VnLHU1DOUlETamq9dg9A7Ogw-5GJ2cu436cAdQM2kqKcilDFuIDnZhaHvqa6dkKRLyHE"
             alt="YFD information desk.">
    </div>
    <div class="absolute inset-0 flex items-center">
        <div class="max-w-container-max mx-auto px-margin-desktop w-full">
            <div class="text-on-primary max-w-3xl">
                <span class="bg-secondary-container text-on-secondary-container inline-block px-3 py-1 font-label-md text-label-md mb-6">PUSAT INFORMASI</span>
                <h1 class="font-display-lg text-display-lg mb-4">Informasi &amp; Kontak YFD</h1>
                <p class="font-body-lg text-body-lg opacity-90 max-w-2xl">
                    Hubungi kami melalui WhatsApp, email, atau social media. Tim YFD aktif di jam kerja
                    dan akan merespon segera.
                </p>
            </div>
        </div>
    </div>
</section>

<main class="max-w-container-max mx-auto px-margin-desktop py-16 space-y-20">

    {{-- ============== Kontak Bento ============== --}}
    <section>
        <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Hubungi Kami</h2>
        <p class="font-body-md text-body-md text-on-surface-variant mb-10 max-w-2xl">
            Pilih channel paling nyaman buat Anda. Untuk konsultasi langsung, WhatsApp adalah jalur tercepat.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
            {{-- WhatsApp --}}
            <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
               class="bg-[#25D366] text-white p-8 rounded-2xl flex flex-col group hover:scale-[1.02] transition-transform shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white/20 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-[32px]" style="font-variation-settings: 'FILL' 1;">chat</span>
                    </div>
                    <span class="material-symbols-outlined opacity-50 group-hover:opacity-100 group-hover:translate-x-1 transition-all">arrow_forward</span>
                </div>
                <h3 class="font-headline-md text-headline-md mb-2">WhatsApp</h3>
                <p class="font-body-md text-body-md opacity-90 mb-3">Channel utama untuk konsultasi & booking.</p>
                <p class="font-label-md text-label-md font-bold mt-auto">{{ $yfd['phone'] }}</p>
            </a>

            {{-- Email --}}
            <a href="mailto:{{ $yfd['email'] }}"
               class="bg-primary-container text-on-primary p-8 rounded-2xl flex flex-col group hover:scale-[1.02] transition-transform shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white/10 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-[32px]">mail</span>
                    </div>
                    <span class="material-symbols-outlined opacity-50 group-hover:opacity-100 group-hover:translate-x-1 transition-all">arrow_forward</span>
                </div>
                <h3 class="font-headline-md text-headline-md mb-2">Email</h3>
                <p class="font-body-md text-body-md opacity-90 mb-3">Untuk kerjasama, recruitment, dan inquiry resmi.</p>
                <p class="font-label-md text-label-md font-bold mt-auto break-all">{{ $yfd['email'] }}</p>
            </a>

            {{-- Instagram --}}
            <a href="https://instagram.com/{{ $yfd['instagram'] }}" target="_blank" rel="noopener"
               class="bg-gradient-to-br from-pink-500 via-red-500 to-yellow-500 text-white p-8 rounded-2xl flex flex-col group hover:scale-[1.02] transition-transform shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white/20 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-[32px]">photo_camera</span>
                    </div>
                    <span class="material-symbols-outlined opacity-50 group-hover:opacity-100 group-hover:translate-x-1 transition-all">arrow_forward</span>
                </div>
                <h3 class="font-headline-md text-headline-md mb-2">Instagram</h3>
                <p class="font-body-md text-body-md opacity-90 mb-3">Konten edukasi finansial harian & update kegiatan.</p>
                <p class="font-label-md text-label-md font-bold mt-auto">@{{ $yfd['instagram'] }}</p>
            </a>

            {{-- TikTok --}}
            <a href="https://tiktok.com/@{{ $yfd['tiktok'] }}" target="_blank" rel="noopener"
               class="bg-black text-white p-8 rounded-2xl flex flex-col group hover:scale-[1.02] transition-transform shadow-lg">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white/10 p-3 rounded-lg">
                        <span class="material-symbols-outlined text-[32px]">music_note</span>
                    </div>
                    <span class="material-symbols-outlined opacity-50 group-hover:opacity-100 group-hover:translate-x-1 transition-all">arrow_forward</span>
                </div>
                <h3 class="font-headline-md text-headline-md mb-2">TikTok</h3>
                <p class="font-body-md text-body-md opacity-70 mb-3">Mini-edukasi finansial dengan format singkat.</p>
                <p class="font-label-md text-label-md font-bold mt-auto">@{{ $yfd['tiktok'] }}</p>
            </a>

            {{-- Founder Card --}}
            <div class="md:col-span-2 bg-surface-container-low border border-outline-variant p-8 rounded-2xl">
                <p class="font-label-md text-label-md text-secondary tracking-widest mb-4">FOUNDER YFD</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-primary-container text-on-primary grid place-items-center font-bold text-xl flex-shrink-0">AB</div>
                        <div>
                            <h4 class="font-headline-md text-[18px] font-bold text-primary">dr. Ayuti Bulaan QWP</h4>
                            <p class="font-caption text-caption text-on-surface-variant">Founder &amp; Financial Doctor</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-primary text-on-primary grid place-items-center font-bold text-xl flex-shrink-0">C</div>
                        <div>
                            <h4 class="font-headline-md text-[18px] font-bold text-primary">dr. Catherine QWP</h4>
                            <p class="font-caption text-caption text-on-surface-variant">Co-Founder &amp; Financial Doctor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============== FAQ ============== --}}
    <section>
        <div class="text-center mb-10">
            <span class="font-label-md text-label-md text-secondary tracking-widest block mb-3">FAQ</span>
            <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Pertanyaan yang Sering Diajukan</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                Belum menemukan jawaban? Tanya langsung di WhatsApp.
            </p>
        </div>

        <div class="max-w-3xl mx-auto space-y-3">
            @forelse($faqs as $faq)
                <details class="group bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
                    <summary class="flex items-center justify-between cursor-pointer p-5 hover:bg-surface-container transition-colors">
                        <div class="flex-1 pr-6">
                            @if($faq->category)
                                <span class="inline-block bg-primary-fixed text-on-primary-fixed px-2 py-0.5 rounded text-[11px] font-bold mb-1">{{ $faq->category }}</span>
                            @endif
                            <h3 class="font-headline-md text-[18px] font-bold text-primary">{{ $faq->question }}</h3>
                        </div>
                        <span class="material-symbols-outlined text-secondary group-open:rotate-180 transition-transform flex-shrink-0">expand_more</span>
                    </summary>
                    <div class="px-5 pb-5 pt-0">
                        <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed border-t border-outline-variant pt-4">
                            {{ $faq->answer }}
                        </p>
                    </div>
                </details>
            @empty
                <div class="text-center py-10 text-on-surface-variant italic">Belum ada FAQ.</div>
            @endforelse
        </div>
    </section>

    {{-- ============== Final CTA ============== --}}
    <section class="bg-primary text-on-primary rounded-2xl p-12 text-center">
        <span class="material-symbols-outlined text-secondary-fixed text-5xl mb-4">support_agent</span>
        <h2 class="font-headline-lg text-headline-lg mb-3">Masih Ada Pertanyaan?</h2>
        <p class="font-body-md text-body-md opacity-80 mb-8 max-w-xl mx-auto">
            Ngobrol langsung dengan tim YFD lebih nyaman. Konsultasi awal di WhatsApp tidak dipungut biaya.
        </p>
        <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-[#25D366] text-white px-10 py-4 rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity shadow-lg">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">chat</span>
            Tanya via WhatsApp
        </a>
    </section>

</main>
@endsection
