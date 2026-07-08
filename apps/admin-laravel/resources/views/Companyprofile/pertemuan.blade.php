@extends('Companyprofile.layouts.main')

@section('title', $isRecovery ? 'Booking Recovery Program — YFD' : 'Booking Pertemuan — YFD')

@push('head')
    <style>
        .custom-shadow {
            box-shadow: 0 4px 6px -1px rgba(13, 43, 78, 0.08), 0 2px 4px -1px rgba(13, 43, 78, 0.08);
        }
    </style>
@endpush

@section('content')

@php
    use App\Support\ConsultationPricing;
    $isRecovery = ($selectedType ?? 'standard') === 'recovery';
    $serviceLabel = $isRecovery ? 'Financial Recovery Program' : 'Financial Consultation';
    $recoveryFrom = ConsultationPricing::formatRupiah($consultationMeta['recovery_from'] ?? 150_000);
@endphp

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

    {{-- ============== Hero / Stepper ============== --}}
    <div class="mb-12 text-center">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-secondary-container/30 text-on-secondary-container font-label-md text-label-md mb-4">
            <span class="material-symbols-outlined text-[18px]">event_available</span>
            ONLINE BOOKING
        </span>
        <h1 class="font-display-lg text-display-lg text-primary mb-4">
            {{ $isRecovery ? 'Booking Financial Recovery Program' : 'Booking Financial Consultation' }}
        </h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto">
            @if($isRecovery)
                Pendampingan intensif untuk kondisi finansial darurat — trauma finansial, krisis hutang, burnout, atau adiksi perilaku finansial.
                Konsultasi awal Recovery mulai <strong>{{ $recoveryFrom }}</strong>/sesi via <strong>WhatsApp</strong>.
            @else
                Konsultasi 1-on-1 dengan tim dokter YFD dilakukan secara <strong>online via WhatsApp</strong>.
                Belum tahu tahap finansial Anda? <a href="{{ $primaryCheckupUrl }}" class="text-primary-container font-semibold underline">Mulai screening gratis</a> dulu.
            @endif
        </p>
    </div>

    @if(!$isRecovery && ($selectedTier ?? null))
        <div class="mb-8 max-w-3xl mx-auto rounded-xl bg-secondary-container/20 border border-secondary-container/40 px-5 py-4 text-sm text-on-surface">
            <strong>Estimasi tarif konsultasi untuk tahap {{ $selectedTier['label'] }}:</strong>
            {{ ConsultationPricing::formatRange($selectedTier) }} {{ $consultationMeta['period'] ?? '/sesi' }}.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

        {{-- ============== Booking Form ============== --}}
        <div class="lg:col-span-8">
            <div class="bg-surface-container-lowest border border-outline-variant p-8 rounded-xl custom-shadow">
                <div class="mb-8">
                    <h2 class="font-headline-lg text-headline-lg text-primary-container mb-2">Informasi Booking</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Form ini menyiapkan pesan WhatsApp otomatis. Tidak ada data yang disimpan di server kami.
                    </p>
                </div>

                <form id="bookingForm" class="space-y-6" onsubmit="return goToWhatsApp(event)">

                    {{-- Layanan --}}
                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Layanan</label>
                        <input type="hidden" name="service" value="{{ $serviceLabel }}">
                        <div class="border-2 border-primary-container rounded-lg p-4 flex items-center gap-3 bg-primary-container/5">
                            <span class="material-symbols-outlined text-primary-container">{{ $isRecovery ? 'healing' : 'stethoscope' }}</span>
                            <div>
                                <span class="font-body-md text-body-md text-on-surface font-semibold block">{{ $serviceLabel }}</span>
                                <span class="font-caption text-caption text-on-surface-variant">
                                    @if($isRecovery)
                                        Konsultasi awal Recovery via WhatsApp — estimasi {{ $recoveryFrom }}/sesi
                                    @else
                                        Konsultasi 1-on-1 dengan dokter finansial YFD via WhatsApp
                                    @endif
                                </span>
                            </div>
                        </div>
                        @if($isRecovery)
                            <p class="font-caption text-caption text-on-surface-variant mt-2">
                                <a href="{{ route('company.bundle.recovery') }}" class="text-primary-container underline">Pelajari program Recovery</a>
                                · Screening gratis tetap tersedia di
                                <a href="{{ $primaryCheckupUrl }}" class="text-primary-container underline">/check-up</a>.
                            </p>
                        @else
                            <p class="font-caption text-caption text-on-surface-variant mt-2">
                                Screening Health Check-Up <strong>gratis</strong> —
                                <a href="{{ $primaryCheckupUrl }}" class="text-primary-container underline">mulai di sini</a>.
                            </p>
                        @endif
                    </div>

                    @if(!$isRecovery)
                    {{-- Tahap finansial (estimasi tarif konsultasi) --}}
                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Tahap Finansial (hasil screening)</label>
                        <select name="stage" id="stageSelect" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                            <option value="">— belum screening / belum tahu —</option>
                            @foreach($consultationTiers as $stageKey => $tier)
                                <option value="{{ $stageKey }}"
                                        data-range="{{ ConsultationPricing::formatRange($tier) }}"
                                        {{ ($selectedStage ?? '') === $stageKey ? 'selected' : '' }}>
                                    {{ $tier['label'] }} — {{ ConsultationPricing::formatRange($tier) }}{{ $consultationMeta['period'] ?? '/sesi' }}
                                </option>
                            @endforeach
                        </select>
                        <p id="stageFeeHint" class="font-caption text-caption text-on-surface-variant mt-2 hidden"></p>
                    </div>
                    @endif

                    @if($isRecovery)
                    <div class="space-y-2">
                        <label for="bf-situation" class="font-label-md text-label-md text-on-surface-variant">Kondisi / Permasalahan Finansial *</label>
                        <select id="bf-situation" name="situation" required class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                            <option value="">— pilih kondisi terdekat —</option>
                            <option>Financial trauma / tekanan finansial kronis</option>
                            <option>Krisis hutang / pinjol / judol</option>
                            <option>Compulsive spending / adiksi perilaku finansial</option>
                            <option>Financial burnout</option>
                            <option>Krisis finansial keluarga</option>
                            <option>Lainnya (jelaskan di bawah)</option>
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
                        <label for="bf-cond" class="font-label-md text-label-md text-on-surface-variant">
                            {{ $isRecovery ? 'Ceritakan situasi Anda (opsional)' : 'Tujuan / Keluhan Finansial Singkat' }}
                        </label>
                        <textarea id="bf-cond" name="condition" rows="4"
                                  placeholder="{{ $isRecovery ? 'Contoh: Hutang pinjol menumpuk, sulit kontrol pengeluaran, butuh pendampingan bertahap...' : 'Contoh: Ingin konsultasi setelah screening, fokus perbaikan cashflow dan utang...' }}"
                                  class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface"></textarea>
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

                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-outline-variant">
                        <button type="submit"
                                class="bg-[#25D366] text-white px-10 py-4 rounded-lg font-label-md text-label-md flex items-center justify-center gap-3 hover:opacity-90 transition-opacity shadow-md">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">chat</span>
                            Lanjut ke WhatsApp YFD
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </div>

                    <p class="font-caption text-caption text-on-surface-variant text-center pt-2">
                        {{ $consultationMeta['multi_session_note'] ?? '' }}
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
                </div>

                <div class="bg-secondary-container/20 border border-secondary-container/40 p-4 rounded-lg flex gap-3 mb-4">
                    <span class="material-symbols-outlined text-secondary-fixed">info</span>
                    <p class="font-caption text-caption opacity-90 leading-relaxed">
                        @if($isRecovery)
                            Konsultasi awal Recovery {{ $recoveryFrom }}/sesi. Tim YFD akan menjelaskan rencana pendampingan setelah sesi pertama.
                        @else
                            Screening gratis di <a href="{{ $primaryCheckupUrl }}" class="underline font-semibold">/check-up</a>.
                            Tarif konsultasi mengikuti tahap finansial hasil screening.
                        @endif
                    </p>
                </div>

                @if(!$isRecovery)
                <a href="{{ $primaryCheckupUrl }}" class="block text-center py-3 rounded-lg bg-secondary-container text-on-secondary-container font-label-md text-label-md hover:brightness-105 transition-all">
                    Belum Screening? Mulai Gratis
                </a>
                @else
                <a href="{{ route('company.bundle.recovery') }}" class="block text-center py-3 rounded-lg bg-secondary-container text-on-secondary-container font-label-md text-label-md hover:brightness-105 transition-all">
                    Pelajari Program Recovery
                </a>
                @endif
            </div>
        </aside>
    </div>
</main>

@push('scripts')
<script>
    const stageSelect = document.getElementById('stageSelect');
    const stageFeeHint = document.getElementById('stageFeeHint');
    const isRecovery = {{ $isRecovery ? 'true' : 'false' }};

    function updateStageHint() {
        if (!stageSelect || !stageFeeHint) return;
        const opt = stageSelect.options[stageSelect.selectedIndex];
        if (!opt || !opt.value) {
            stageFeeHint.classList.add('hidden');
            return;
        }
        stageFeeHint.textContent = 'Estimasi tarif konsultasi: ' + (opt.dataset.range || '-') + '/sesi (bisa berbeda sesuai kompleksitas kasus).';
        stageFeeHint.classList.remove('hidden');
    }
    if (stageSelect) {
        stageSelect.addEventListener('change', updateStageHint);
        updateStageHint();
    }

    function goToWhatsApp(event) {
        event.preventDefault();
        const form = document.getElementById('bookingForm');
        const fd = new FormData(form);

        const service = fd.get('service') || '-';
        const stage = fd.get('stage') || '';
        const situation = fd.get('situation') || '';
        const name = fd.get('name') || '-';
        const age = fd.get('age') || '';
        const condition = fd.get('condition') || '';
        const time = fd.get('time') || '';

        let stageLabel = '';
        if (stage && stageSelect) {
            const opt = stageSelect.options[stageSelect.selectedIndex];
            stageLabel = opt ? opt.textContent.trim() : stage;
        }

        let lines = [];
        lines.push(isRecovery
            ? "Halo Tim YFD, saya ingin booking Financial Recovery Program."
            : "Halo Tim YFD, saya ingin booking konsultasi finansial.");
        lines.push("");
        lines.push("*Nama:* " + name);
        if (age) lines.push("*Usia:* " + age);
        lines.push("*Layanan:* " + service);
        if (situation) lines.push("*Kondisi:* " + situation);
        if (stageLabel) {
            lines.push("*Tahap finansial (hasil screening):* " + stageLabel);
        }
        if (condition) {
            lines.push("");
            lines.push("*Cerita / keluhan:*");
            lines.push(condition);
        }
        if (time) {
            lines.push("");
            lines.push("*Preferensi waktu:* " + time);
        }
        lines.push("");
        lines.push(isRecovery
            ? "Mohon info jadwal konsultasi awal & rencana pendampingan. Terima kasih."
            : "Mohon info jadwal & estimasi biaya sesi. Terima kasih.");

        const text = encodeURIComponent(lines.join("\n"));
        const url = "https://wa.me/{{ $yfd['wa_number'] }}?text=" + text;
        window.open(url, '_blank', 'noopener');
        return false;
    }
</script>
@endpush

@endsection
