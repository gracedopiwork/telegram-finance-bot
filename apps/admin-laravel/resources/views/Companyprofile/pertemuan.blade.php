@extends('Companyprofile.layouts.main')

@section('title', 'Booking Pertemuan — YFD')

@push('head')
    <style>
        .custom-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 51, 102, 0.08), 0 2px 4px -1px rgba(0, 51, 102, 0.08);
        }
    </style>
@endpush

@section('content')

@php
    $selectedPlan = request('plan');
    $selectedPkg  = collect($packages ?? [])->firstWhere('code', $selectedPlan) ?? ($packages[1] ?? ($packages[0] ?? null));
@endphp

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

    {{-- ============== Hero / Stepper ============== --}}
    <div class="mb-12 text-center">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-secondary-container/30 text-on-secondary-container font-label-md text-label-md mb-4">
            <span class="material-symbols-outlined text-[18px]">event_available</span>
            ONLINE BOOKING
        </span>
        <h1 class="font-display-lg text-display-lg text-primary mb-4">Rencanakan Pertemuan Anda</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto">
            Konsultasi YFD dilakukan secara <strong>online via WhatsApp</strong>. Isi form di bawah, lalu
            klik tombol untuk lanjut ke chat WhatsApp dengan pesan otomatis terkirim.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

        {{-- ============== Booking Form ============== --}}
        <div class="lg:col-span-8">
            <div class="bg-surface-container-lowest border border-outline-variant p-8 rounded-xl custom-shadow">
                <div class="mb-8">
                    <h2 class="font-headline-lg text-headline-lg text-primary-container mb-2">Informasi Booking</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Form ini hanya untuk menyiapkan pesan otomatis. Tidak ada data yang disimpan di server kami.
                    </p>
                </div>

                {{-- Form: build WhatsApp URL via JS on submit --}}
                <form id="bookingForm" class="space-y-6" onsubmit="return goToWhatsApp(event)">

                    {{-- Layanan --}}
                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Pilih Layanan *</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach([
                                ['v' => 'health-check-up',     'ic' => 'monitor_heart',  'label' => 'Financial Health Check Up'],
                                ['v' => 'consultation',        'ic' => 'stethoscope',    'label' => 'Financial Consultation'],
                                ['v' => 'recovery-program',    'ic' => 'healing',        'label' => 'Financial Recovery Program'],
                                ['v' => 'education-platform',  'ic' => 'school',         'label' => 'Education / Webinar'],
                                ['v' => 'digital-monitoring',  'ic' => 'monitoring',     'label' => 'Digital Monitoring Bot'],
                                ['v' => 'other',               'ic' => 'help',           'label' => 'Lainnya / Belum Tahu'],
                            ] as $idx => $opt)
                                <label class="cursor-pointer relative">
                                    <input type="radio" name="service" value="{{ $opt['label'] }}" {{ $idx === 0 ? 'checked' : '' }} class="peer sr-only" required>
                                    <div class="border-2 border-outline-variant rounded-lg p-4 flex items-center gap-3 hover:border-primary-container peer-checked:border-primary-container peer-checked:bg-primary-container/5 transition-all">
                                        <span class="material-symbols-outlined text-primary-container">{{ $opt['ic'] }}</span>
                                        <span class="font-body-md text-body-md text-on-surface">{{ $opt['label'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Paket (kalau Health Check Up) --}}
                    @if(!empty($packages))
                        <div id="packageWrapper">
                            <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Paket (jika Health Check Up)</label>
                            <select name="package" id="packageSelect" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                                <option value="">— tidak relevan / belum tahu —</option>
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg['name'] }} (Rp {{ number_format($pkg['price'], 0, ',', '.') }})"
                                            {{ $selectedPkg && $selectedPkg['code'] === $pkg['code'] ? 'selected' : '' }}>
                                        {{ $pkg['name'] }} — Rp {{ number_format($pkg['price'], 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Nama --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="bf-name" class="font-label-md text-label-md text-on-surface-variant">Nama Lengkap *</label>
                            <input id="bf-name" type="text" name="name" required placeholder="John Doe"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                        <div class="space-y-2">
                            <label for="bf-age" class="font-label-md text-label-md text-on-surface-variant">Usia (opsional)</label>
                            <input id="bf-age" type="number" name="age" min="15" max="99" placeholder="contoh: 28"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                    </div>

                    {{-- Kondisi singkat --}}
                    <div class="space-y-2">
                        <label for="bf-cond" class="font-label-md text-label-md text-on-surface-variant">Kondisi / Keluhan Finansial Singkat</label>
                        <textarea id="bf-cond" name="condition" rows="4" placeholder="Contoh: Saya kesulitan mengatur cashflow bulanan dan ada cicilan yang tertunda..."
                                  class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface"></textarea>
                        <p class="font-caption text-caption text-on-surface-variant">Cukup gambaran umum saja — detailnya nanti bisa di-share langsung di WhatsApp.</p>
                    </div>

                    {{-- Preferred time --}}
                    <div class="space-y-2">
                        <label for="bf-time" class="font-label-md text-label-md text-on-surface-variant">Preferensi Waktu</label>
                        <select id="bf-time" name="time" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                            <option value="">— bebas / mengikuti jadwal YFD —</option>
                            <option>Pagi (08.00 - 11.00)</option>
                            <option>Siang (11.00 - 14.00)</option>
                            <option>Sore (14.00 - 17.00)</option>
                            <option>Malam (17.00 - 20.00)</option>
                            <option>Weekend (Sabtu/Minggu)</option>
                        </select>
                    </div>

                    {{-- Submit --}}
                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-outline-variant">
                        <button type="submit"
                                class="bg-[#25D366] text-white px-10 py-4 rounded-lg font-label-md text-label-md flex items-center justify-center gap-3 hover:opacity-90 transition-opacity shadow-md">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">chat</span>
                            Lanjut ke WhatsApp YFD
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </div>

                    <p class="font-caption text-caption text-on-surface-variant text-center pt-2">
                        Dengan klik tombol di atas, Anda akan diarahkan ke chat WhatsApp dengan pesan
                        sudah terisi otomatis. Tinggal kirim, dan tim YFD akan membalas.
                    </p>
                </form>
            </div>
        </div>

        {{-- ============== Sidebar Info ============== --}}
        <aside class="lg:col-span-4">
            <div class="bg-primary-container text-on-primary p-6 rounded-xl sticky top-28 shadow-xl">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-secondary-container text-on-secondary-container w-12 h-12 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined">support_agent</span>
                    </div>
                    <div>
                        <p class="font-label-md text-label-md text-secondary-fixed">Tim YFD</p>
                        <p class="font-body-md text-body-md font-bold">Online & Siap Bantu</p>
                    </div>
                </div>
                <h2 class="font-headline-md text-headline-md mb-3">Kontak Langsung</h2>
                <div class="space-y-3 mb-6">
                    <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="flex items-center gap-3 hover:text-secondary-fixed-dim">
                        <span class="material-symbols-outlined">phone_in_talk</span>
                        <span class="font-body-md text-body-md">{{ $yfd['phone'] }}</span>
                    </a>
                    <a href="mailto:{{ $yfd['email'] }}" class="flex items-center gap-3 hover:text-secondary-fixed-dim">
                        <span class="material-symbols-outlined">mail</span>
                        <span class="font-body-md text-body-md break-all">{{ $yfd['email'] }}</span>
                    </a>
                    <a href="{{ \App\Support\SocialUrl::instagram($yfd['instagram']) }}" target="_blank" rel="noopener" class="flex items-center gap-3 hover:text-secondary-fixed-dim">
                        <span class="material-symbols-outlined">photo_camera</span>
                        <span class="font-body-md text-body-md">Instagram</span>
                    </a>
                    <a href="{{ \App\Support\SocialUrl::tiktok($yfd['tiktok']) }}" target="_blank" rel="noopener" class="flex items-center gap-3 hover:text-secondary-fixed-dim">
                        <span class="material-symbols-outlined">music_note</span>
                        <span class="font-body-md text-body-md">TikTok</span>
                    </a>
                    <a href="{{ \App\Support\SocialUrl::threads($yfd['threads']) }}" target="_blank" rel="noopener" class="flex items-center gap-3 hover:text-secondary-fixed-dim">
                        <span class="material-symbols-outlined">alternate_email</span>
                        <span class="font-body-md text-body-md">Threads</span>
                    </a>
                </div>

                <div class="border-t border-on-primary/20 pt-4 mb-6">
                    <p class="font-label-md text-label-md text-secondary-fixed mb-3">FOUNDER</p>
                    <p class="font-body-md text-body-md font-bold">dr. Ayuti Bulaan QWP</p>
                    <p class="font-caption text-caption opacity-80 mb-2">Founder &amp; Financial Doctor</p>
                    <p class="font-body-md text-body-md font-bold mt-3">dr. Catherine QWP</p>
                    <p class="font-caption text-caption opacity-80">Co-Founder &amp; Financial Doctor</p>
                </div>

                <div class="bg-secondary-container/20 border border-secondary-container/40 p-4 rounded-lg flex gap-3">
                    <span class="material-symbols-outlined text-secondary-fixed">info</span>
                    <p class="font-caption text-caption opacity-90 leading-relaxed">
                        Setelah Anda klik "Lanjut ke WhatsApp", pesan otomatis berisi data yang Anda isi
                        akan tampil. Tim kami akan respon di jam kerja.
                    </p>
                </div>
            </div>
        </aside>
    </div>

    {{-- ============== Why YFD ============== --}}
    <div class="mt-20">
        <div class="text-center mb-10">
            <h2 class="font-headline-lg text-headline-lg text-primary mb-3">Mengapa Booking dengan YFD?</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                Kami pendekatan personal — tidak ada janji instan, tidak ada manipulasi.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
            @foreach([
                ['ic' => 'verified',         'title' => 'Tanpa Janji Instan',       'desc' => 'Kami tidak menjual mimpi cepat kaya. Kami fokus pada pondasi yang sehat dan tahan krisis.'],
                ['ic' => 'health_and_safety','title' => 'Tim Dokter Profesional',   'desc' => 'Founder YFD adalah dokter umum yang melihat finansial dari sudut pandang kesehatan manusia.'],
                ['ic' => 'lock',             'title' => 'Privasi Terjamin',         'desc' => 'Semua diskusi bersifat confidential. Data Anda tidak dibagikan ke pihak ketiga.'],
            ] as $card)
                <div class="bg-surface-container-lowest border border-outline-variant p-6 rounded-xl">
                    <div class="w-12 h-12 bg-primary-container/10 rounded-lg flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-primary-container">{{ $card['ic'] }}</span>
                    </div>
                    <h3 class="font-headline-md text-[18px] font-bold text-primary mb-2">{{ $card['title'] }}</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant">{{ $card['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</main>

@push('scripts')
<script>
    function goToWhatsApp(event) {
        event.preventDefault();
        const form = document.getElementById('bookingForm');
        const fd = new FormData(form);

        const service = fd.get('service') || '-';
        const package_ = fd.get('package') || '';
        const name = fd.get('name') || '-';
        const age = fd.get('age') || '';
        const condition = fd.get('condition') || '';
        const time = fd.get('time') || '';

        let lines = [];
        lines.push("Halo Tim YFD, saya ingin booking konsultasi finansial.");
        lines.push("");
        lines.push("*Nama:* " + name);
        if (age) lines.push("*Usia:* " + age);
        lines.push("*Layanan:* " + service);
        if (package_) lines.push("*Paket:* " + package_);
        if (condition) {
            lines.push("");
            lines.push("*Keluhan singkat:*");
            lines.push(condition);
        }
        if (time) {
            lines.push("");
            lines.push("*Preferensi waktu:* " + time);
        }
        lines.push("");
        lines.push("Mohon info jadwal & langkah berikutnya. Terima kasih.");

        const text = encodeURIComponent(lines.join("\n"));
        const url = "https://wa.me/{{ $yfd['wa_number'] }}?text=" + text;

        window.open(url, '_blank', 'noopener');
        return false;
    }
</script>
@endpush

@endsection
