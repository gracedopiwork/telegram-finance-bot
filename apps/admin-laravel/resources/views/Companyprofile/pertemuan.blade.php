@extends('Companyprofile.layouts.main')

@php
    $isRecovery = ($selectedType ?? 'standard') === 'recovery';
@endphp

@section('title', $isRecovery ? 'Booking Recovery Program — YFD' : 'Booking Pertemuan — YFD')

@push('head')
    <style>
        .custom-shadow {
            box-shadow: 0 4px 6px -1px rgba(13, 43, 78, 0.08), 0 2px 4px -1px rgba(13, 43, 78, 0.08);
        }
        .slot-chip {
            min-width: 5.5rem;
        }
        .slot-chip input:checked + span {
            background: #0d2b4e;
            color: #fff;
            border-color: #0d2b4e;
        }
    </style>
@endpush

@section('content')

@php
    use App\Support\ConsultationPricing;
    $serviceLabel = $isRecovery ? 'Financial Recovery Program' : 'Financial Consultation';
    $recoveryFrom = ConsultationPricing::formatRupiah($consultationMeta['recovery_from'] ?? 150_000);
    $hasSlots = ($openSlots ?? collect())->isNotEmpty();
@endphp

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

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
                Pendampingan intensif untuk kondisi finansial darurat.
                Pilih jadwal available, lanjut WhatsApp — admin verifikasi pembayaran untuk mengunci slot.
            @else
                Konsultasi 1-on-1 dengan dokter YFD dilakukan secara <strong>online via WhatsApp</strong>.
                Pilih dokter dan jadwal yang tersedia, lalu lanjut ke WhatsApp.
                Belum screening? <a href="{{ $primaryCheckupUrl }}" class="text-primary-container font-semibold underline">Mulai gratis</a>.
            @endif
        </p>
    </div>

    @if(!$isRecovery && ($selectedTier ?? null))
        <div class="mb-8 max-w-3xl mx-auto rounded-xl bg-secondary-container/20 border border-secondary-container/40 px-5 py-4 text-sm text-on-surface">
            <strong>Estimasi tarif konsultasi untuk tahap {{ $selectedTier['label'] }}:</strong>
            {{ ConsultationPricing::formatRange($selectedTier) }} {{ $consultationMeta['period'] ?? '/sesi' }}.
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 max-w-3xl mx-auto rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 max-w-3xl mx-auto rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
        <div class="lg:col-span-8">
            <div class="bg-surface-container-lowest border border-outline-variant p-8 rounded-xl custom-shadow">
                <div class="mb-8">
                    <h2 class="font-headline-lg text-headline-lg text-primary-container mb-2">Informasi Booking</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Pilih dokter &amp; slot available. Setelah submit, slot di-hold {{ $holdMinutes ?? 45 }} menit
                        sampai admin verifikasi pembayaran.
                    </p>
                </div>

                @if(!$hasSlots)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 mb-6">
                        Belum ada jadwal available saat ini. Silakan
                        <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="underline font-semibold">hubungi WhatsApp YFD</a>
                        untuk info jadwal, atau coba lagi nanti.
                    </div>
                @endif

                <form id="bookingForm" method="POST" action="{{ route('company.pertemuan.book') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="service_type" value="{{ $isRecovery ? 'recovery' : 'standard' }}">

                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Layanan</label>
                        <div class="border-2 border-primary-container rounded-lg p-4 flex items-center gap-3 bg-primary-container/5">
                            <span class="material-symbols-outlined text-primary-container">{{ $isRecovery ? 'healing' : 'stethoscope' }}</span>
                            <div>
                                <span class="font-body-md text-body-md text-on-surface font-semibold block">{{ $serviceLabel }}</span>
                                <span class="font-caption text-caption text-on-surface-variant">
                                    @if($isRecovery)
                                        Estimasi mulai {{ $recoveryFrom }}/sesi
                                    @else
                                        Konsultasi 1-on-1 via WhatsApp
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Dokter --}}
                    <div class="space-y-2">
                        <label for="advisorSelect" class="font-label-md text-label-md text-on-surface-variant">Pilih Dokter *</label>
                        <select id="advisorSelect" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface" {{ $hasSlots ? 'required' : 'disabled' }}>
                            <option value="">— pilih dokter —</option>
                            @foreach($advisors as $adv)
                                @php $advHas = isset($datesByAdvisor[$adv->id]) && count($datesByAdvisor[$adv->id]) > 0; @endphp
                                <option value="{{ $adv->id }}"
                                        data-dates='@json($datesByAdvisor[$adv->id] ?? [])'
                                        {{ !$advHas ? 'disabled' : '' }}
                                        {{ (string) old('advisor_id') === (string) $adv->id ? 'selected' : '' }}>
                                    {{ $adv->name }}{{ $advHas ? '' : ' (belum ada slot)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal --}}
                    <div class="space-y-2">
                        <label for="dateSelect" class="font-label-md text-label-md text-on-surface-variant">Tanggal *</label>
                        <select id="dateSelect" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface" disabled>
                            <option value="">— pilih dokter dulu —</option>
                        </select>
                    </div>

                    {{-- Slot grid --}}
                    <div class="space-y-2">
                        <label class="font-label-md text-label-md text-on-surface-variant">Pilih Jam *</label>
                        <div id="slotGrid" class="flex flex-wrap gap-2 min-h-[3rem]">
                            <p class="text-sm text-on-surface-variant">Pilih dokter &amp; tanggal untuk melihat slot.</p>
                        </div>
                        <input type="hidden" name="slot_id" id="slotIdInput" value="{{ old('slot_id') }}" {{ $hasSlots ? 'required' : '' }}>
                        <p class="font-caption text-caption text-on-surface-variant">
                            Slot yang sudah dipilih orang lain tidak muncul. Setelah Anda submit, slot tertutup untuk orang lain selama {{ $holdMinutes ?? 45 }} menit.
                        </p>
                    </div>

                    @if(!$isRecovery)
                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Tahap Finansial (hasil screening)</label>
                        <select name="stage" id="stageSelect" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                            <option value="">— belum screening / belum tahu —</option>
                            @foreach($consultationTiers as $stageKey => $tier)
                                <option value="{{ $stageKey }}"
                                        data-range="{{ ConsultationPricing::formatRange($tier) }}"
                                        {{ ($selectedStage ?? '') === $stageKey || old('stage') === $stageKey ? 'selected' : '' }}>
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
                            @foreach([
                                'Financial trauma / tekanan finansial kronis',
                                'Krisis hutang / pinjol / judol',
                                'Compulsive spending / adiksi perilaku finansial',
                                'Financial burnout',
                                'Krisis finansial keluarga',
                                'Lainnya (jelaskan di bawah)',
                            ] as $opt)
                                <option {{ old('situation') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="bf-name" class="font-label-md text-label-md text-on-surface-variant">Nama Lengkap *</label>
                            <input id="bf-name" type="text" name="name" required value="{{ old('name') }}" placeholder="Nama lengkap"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                        <div class="space-y-2">
                            <label for="bf-phone" class="font-label-md text-label-md text-on-surface-variant">No. WhatsApp (opsional)</label>
                            <input id="bf-phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="08…"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="bf-age" class="font-label-md text-label-md text-on-surface-variant">Usia (opsional)</label>
                            <input id="bf-age" type="number" name="age" min="15" max="99" value="{{ old('age') }}" placeholder="contoh: 28"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="bf-cond" class="font-label-md text-label-md text-on-surface-variant">
                            {{ $isRecovery ? 'Ceritakan situasi Anda (opsional)' : 'Tujuan / Keluhan Finansial Singkat' }}
                        </label>
                        <textarea id="bf-cond" name="condition" rows="4"
                                  placeholder="{{ $isRecovery ? 'Contoh: Hutang pinjol menumpuk…' : 'Contoh: Fokus perbaikan cashflow…' }}"
                                  class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">{{ old('condition') }}</textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-outline-variant">
                        <button type="submit" {{ $hasSlots ? '' : 'disabled' }}
                                class="bg-[#25D366] text-white px-10 py-4 rounded-lg font-label-md text-label-md flex items-center justify-center gap-3 hover:opacity-90 transition-opacity shadow-md disabled:opacity-50">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">chat</span>
                            Hold Slot &amp; Lanjut WhatsApp
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </div>

                    <p class="font-caption text-caption text-on-surface-variant text-center pt-2">
                        {{ $consultationMeta['multi_session_note'] ?? '' }}
                    </p>
                </form>
            </div>
        </div>

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
                <a href="{{ $waBookingUrl }}" target="_blank" rel="noopener" class="flex items-center gap-3 hover:text-secondary-fixed-dim mb-4">
                    <span class="material-symbols-outlined">phone_in_talk</span>
                    <span class="font-body-md text-body-md">{{ $yfd['phone'] }}</span>
                </a>
                <div class="bg-secondary-container/20 border border-secondary-container/40 p-4 rounded-lg flex gap-3 mb-4">
                    <span class="material-symbols-outlined text-secondary-fixed">info</span>
                    <p class="font-caption text-caption opacity-90 leading-relaxed">
                        Pilih jadwal available, lalu lanjut WhatsApp. Admin akan mengonfirmasi pembayaran untuk mengunci jadwal.
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
(function () {
    const advisorSelect = document.getElementById('advisorSelect');
    const dateSelect = document.getElementById('dateSelect');
    const slotGrid = document.getElementById('slotGrid');
    const slotIdInput = document.getElementById('slotIdInput');
    const slotsUrl = @json(route('company.pertemuan.slots'));
    const stageSelect = document.getElementById('stageSelect');
    const stageFeeHint = document.getElementById('stageFeeHint');

    function updateStageHint() {
        if (!stageSelect || !stageFeeHint) return;
        const opt = stageSelect.options[stageSelect.selectedIndex];
        if (!opt || !opt.value) {
            stageFeeHint.classList.add('hidden');
            return;
        }
        stageFeeHint.textContent = 'Estimasi tarif konsultasi: ' + (opt.dataset.range || '-') + '/sesi.';
        stageFeeHint.classList.remove('hidden');
    }
    if (stageSelect) {
        stageSelect.addEventListener('change', updateStageHint);
        updateStageHint();
    }

    function clearSlots(msg) {
        slotGrid.innerHTML = '<p class="text-sm text-on-surface-variant">' + (msg || 'Tidak ada slot.') + '</p>';
        slotIdInput.value = '';
    }

    function fillDates() {
        const opt = advisorSelect.options[advisorSelect.selectedIndex];
        dateSelect.innerHTML = '';
        if (!opt || !opt.value) {
            dateSelect.disabled = true;
            dateSelect.innerHTML = '<option value="">— pilih dokter dulu —</option>';
            clearSlots('Pilih dokter & tanggal untuk melihat slot.');
            return;
        }
        let dates = [];
        try { dates = JSON.parse(opt.dataset.dates || '[]'); } catch (e) { dates = []; }
        if (!dates.length) {
            dateSelect.disabled = true;
            dateSelect.innerHTML = '<option value="">Tidak ada tanggal available</option>';
            clearSlots('Dokter ini belum punya slot open.');
            return;
        }
        dateSelect.disabled = false;
        dateSelect.innerHTML = '<option value="">— pilih tanggal —</option>';
        dates.forEach(function (d) {
            const o = document.createElement('option');
            o.value = d;
            o.textContent = d;
            dateSelect.appendChild(o);
        });
        clearSlots('Pilih tanggal untuk melihat jam available.');
    }

    function loadSlots() {
        const advisorId = advisorSelect.value;
        const date = dateSelect.value;
        if (!advisorId || !date) {
            clearSlots('Pilih tanggal untuk melihat jam available.');
            return;
        }
        slotGrid.innerHTML = '<p class="text-sm text-on-surface-variant">Memuat slot…</p>';
        fetch(slotsUrl + '?advisor_id=' + encodeURIComponent(advisorId) + '&date=' + encodeURIComponent(date), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const slots = data.slots || [];
                if (!slots.length) {
                    clearSlots('Tidak ada jam available di tanggal ini.');
                    return;
                }
                slotGrid.innerHTML = '';
                slots.forEach(function (s) {
                    const label = document.createElement('label');
                    label.className = 'slot-chip cursor-pointer';
                    label.innerHTML =
                        '<input type="radio" name="_slot_choice" value="' + s.id + '" class="sr-only">' +
                        '<span class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-outline-variant text-sm font-semibold bg-white hover:border-primary-container">' +
                        s.label +
                        '</span>';
                    label.querySelector('input').addEventListener('change', function () {
                        slotIdInput.value = s.id;
                    });
                    slotGrid.appendChild(label);
                });
            })
            .catch(function () {
                clearSlots('Gagal memuat slot. Refresh halaman.');
            });
    }

    if (advisorSelect) {
        advisorSelect.addEventListener('change', fillDates);
        dateSelect.addEventListener('change', loadSlots);
        fillDates();
    }

    document.getElementById('bookingForm').addEventListener('submit', function (e) {
        if (!slotIdInput.value) {
            e.preventDefault();
            alert('Pilih jam available terlebih dahulu.');
        }
    });
})();
</script>
@endpush

@endsection
