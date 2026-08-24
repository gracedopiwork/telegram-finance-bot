@extends('Companyprofile.layouts.main')

@php
    $isRecovery = ($selectedType ?? 'standard') === 'recovery';
    $isPremarital = ($selectedType ?? 'standard') === 'premarital';
@endphp

@section('title', $isPremarital ? 'Booking Premarital — YFD' : ($isRecovery ? 'Booking Recovery Program — YFD' : 'Booking Pertemuan — YFD'))

@push('head')
    <style>
        .custom-shadow {
            box-shadow: 0 4px 6px -1px rgba(13, 43, 78, 0.08), 0 2px 4px -1px rgba(13, 43, 78, 0.08);
        }
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.4rem;
        }
        .cal-dow {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #6b7c8f;
            padding: 0.25rem 0 0.5rem;
        }
        .cal-cell {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            border-radius: 0.55rem;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: #a0aec0;
            cursor: default;
            padding: 0;
            line-height: 1;
        }
        .cal-cell.is-empty { visibility: hidden; pointer-events: none; }
        .cal-cell.is-available {
            background: #e8f3ec;
            color: #1f5c45;
            cursor: pointer;
        }
        .cal-cell.is-available:hover { background: #d7ebe0; }
        .cal-cell.is-selected {
            background: #0d2b4e;
            color: #fff;
            cursor: pointer;
        }
        .cal-dot {
            width: 4px;
            height: 4px;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.85;
        }
        .cal-cell:not(.is-available):not(.is-selected) .cal-dot { display: none; }
        .slot-chip span {
            min-width: 4.75rem;
            background: #eef7f1;
            border: 1.5px solid #2f6b55;
            color: #1f5c45;
        }
        .slot-chip input:checked + span {
            background: #0d2b4e;
            color: #fff;
            border-color: #0d2b4e;
        }
        .cal-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            border: 1px solid #d0d7e0;
            background: #fff;
            color: #0d2b4e;
            cursor: pointer;
        }
        .cal-nav-btn:hover { background: #f3f6f9; }
        .cal-nav-btn:disabled { opacity: 0.35; cursor: default; }
    </style>
@endpush

@section('content')

@php
    use App\Support\ConsultationPricing;
    $serviceLabel = $isPremarital
        ? 'Premarital Financial Health Check Up'
        : ($isRecovery ? 'Financial Recovery Program' : 'Financial Consultation');
    $recoveryFrom = ConsultationPricing::formatRupiah($consultationMeta['recovery_from'] ?? 150_000);
    $hasSlots = ($openSlots ?? collect())->isNotEmpty();
    $serviceTypeValue = $isPremarital ? 'premarital' : ($isRecovery ? 'recovery' : 'standard');
@endphp

<main class="max-w-container-max mx-auto px-margin-desktop py-12">

    <div class="mb-12 text-center">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-secondary-container/30 text-on-secondary-container font-label-md text-label-md mb-4">
            <span class="material-symbols-outlined text-[18px]">event_available</span>
            {{ ($page['page.pertemuan.hero_badge'] ?? null) ?: 'ONLINE BOOKING' }}
        </span>
        <h1 class="font-display-lg text-display-lg text-primary mb-4">
            @if($isPremarital)
                {{ ($page['page.pertemuan.hero_title_premarital'] ?? null) ?: 'Booking Premarital Check Up' }}
            @elseif($isRecovery)
                {{ ($page['page.pertemuan.hero_title_recovery'] ?? null) ?: 'Booking Financial Recovery Program' }}
            @else
                {{ ($page['page.pertemuan.hero_title_standard'] ?? null) ?: 'Booking Financial Consultation' }}
            @endif
        </h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto">
            @if($isPremarital)
                {!! nl2br(e(($page['page.pertemuan.hero_subtitle_premarital'] ?? null) ?: 'Karena 2 orang yang konsultasi, pilih dokter di awal agar sesi male, female, dan couple ditangani dokter yang sama. Pilih tanggal & jam, lalu bayar untuk mengunci slot.')) !!}
            @elseif($isRecovery)
                {!! nl2br(e(($page['page.pertemuan.hero_subtitle_recovery'] ?? null) ?: 'Pendampingan intensif untuk kondisi finansial darurat. Pilih tanggal & jam available, lalu bayar via payment gateway untuk mengunci slot.')) !!}
            @else
                {!! nl2br(e(($page['page.pertemuan.hero_subtitle_standard'] ?? null) ?: 'Konsultasi 1-on-1 dengan dokter YFD secara online. Pilih tanggal & jam tersedia, bayar sesuai tahap FHCU, baru jadwal dikunci.')) !!}
                Belum screening? <a href="{{ $primaryCheckupUrl }}" class="text-primary-container font-semibold underline">Mulai gratis</a>.
            @endif
        </p>
    </div>

    @if(!$isRecovery && !$isPremarital && ($selectedTier ?? null))
        <div class="mb-8 max-w-3xl mx-auto rounded-xl bg-secondary-container/20 border border-secondary-container/40 px-5 py-4 text-sm text-on-surface">
            <strong>Tarif sesi untuk tahap {{ $selectedTier['label'] }}:</strong>
            {{ ConsultationPricing::formatRange($selectedTier) }} {{ $consultationMeta['period'] ?? '/sesi' }}.
        </div>
    @endif

    @if(!empty($overtimeDisclosure))
        <div class="mb-8 max-w-3xl mx-auto rounded-xl border border-outline-variant bg-surface-container-lowest px-5 py-4 text-sm text-on-surface-variant">
            {{ $overtimeDisclosure }}
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
                        Setelah submit, slot di-hold {{ $holdMinutes ?? 45 }} menit sementara Anda menyelesaikan pembayaran. Jadwal terkunci otomatis setelah status LUNAS.
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
                    <input type="hidden" name="service_type" value="{{ $serviceTypeValue }}">
                    @if($isPremarital)
                        <input type="hidden" name="advisor_id" id="advisorIdInput" value="{{ old('advisor_id') }}">
                    @endif

                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Layanan</label>
                        <div class="border-2 border-primary-container rounded-lg p-4 flex items-center gap-3 bg-primary-container/5">
                            <span class="material-symbols-outlined text-primary-container">{{ $isPremarital ? 'diversity_1' : ($isRecovery ? 'healing' : 'stethoscope') }}</span>
                            <div>
                                <span class="font-body-md text-body-md text-on-surface font-semibold block">{{ $serviceLabel }}</span>
                                <span class="font-caption text-caption text-on-surface-variant">
                                    @if($isPremarital)
                                        Sesi individu + couple · dokter yang sama
                                    @elseif($isRecovery)
                                        Estimasi mulai {{ $recoveryFrom }}/sesi
                                    @else
                                        Konsultasi 1-on-1 via WhatsApp
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($isPremarital)
                    <div class="space-y-2">
                        <label for="advisorSelect" class="font-label-md text-label-md text-on-surface-variant">Pilih Dokter *</label>
                        <select id="advisorSelect" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface" {{ $hasSlots ? 'required' : 'disabled' }}>
                            <option value="">— pilih dokter yang akan menangani pasangan —</option>
                            @foreach(($advisors ?? []) as $adv)
                                @php $advHas = isset($datesByAdvisor[$adv->id]) && count($datesByAdvisor[$adv->id]) > 0; @endphp
                                <option value="{{ $adv->id }}"
                                        data-dates='@json($datesByAdvisor[$adv->id] ?? [])'
                                        {{ !$advHas ? 'disabled' : '' }}
                                        {{ (string) old('advisor_id') === (string) $adv->id ? 'selected' : '' }}>
                                    {{ $adv->name }}{{ $advHas ? '' : ' (belum ada slot)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="font-caption text-caption text-on-surface-variant">
                            Dokter ini dipakai untuk sesi male, female, dan couple seterusnya.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="sessionRole" class="font-label-md text-label-md text-on-surface-variant">Jenis sesi yang dibooking sekarang *</label>
                        <select id="sessionRole" name="session_role" required class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                            <option value="">— pilih —</option>
                            <option value="male" {{ old('session_role') === 'male' ? 'selected' : '' }}>Sesi 1-on-1 male</option>
                            <option value="female" {{ old('session_role') === 'female' ? 'selected' : '' }}>Sesi 1-on-1 female</option>
                            <option value="couple" {{ old('session_role') === 'couple' ? 'selected' : '' }}>Sesi couple (+ follow-up dijadwalkan admin)</option>
                        </select>
                    </div>
                    @endif

                    {{-- Kalender tanggal + jam --}}
                    <div id="calendarBlock" class="space-y-4 {{ $isPremarital ? 'opacity-60' : '' }}">
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            @if($isPremarital)
                                Setelah pilih dokter, pilih tanggal &amp; jam available dokter tersebut.
                            @else
                                Pilih tanggal &amp; jam yang tersedia. Sesi berlangsung ±1 jam, ditampilkan berjarak 2 jam antar sesi.
                            @endif
                        </p>

                        <div class="flex items-center justify-between gap-3">
                            <button type="button" id="calPrev" class="cal-nav-btn" aria-label="Bulan sebelumnya">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </button>
                            <p id="calMonthLabel" class="font-label-md text-label-md text-primary font-semibold"></p>
                            <button type="button" id="calNext" class="cal-nav-btn" aria-label="Bulan berikutnya">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </button>
                        </div>

                        <div class="cal-grid" id="calDow" aria-hidden="true">
                            @foreach(['MIN','SEN','SEL','RAB','KAM','JUM','SAB'] as $dow)
                                <div class="cal-dow">{{ $dow }}</div>
                            @endforeach
                        </div>
                        <div class="cal-grid" id="calDays" role="grid" aria-label="Kalender booking"></div>

                        <div id="timePanel" class="pt-2 space-y-3 {{ $hasSlots && !$isPremarital ? '' : '' }}">
                            <p id="selectedDateLabel" class="font-label-md text-label-md text-primary font-semibold"></p>
                            <div id="slotGrid" class="flex flex-wrap gap-2 min-h-[2.75rem]">
                                <p class="text-sm text-on-surface-variant">
                                    {{ $isPremarital ? 'Pilih dokter dulu, lalu tanggal.' : 'Pilih tanggal untuk melihat jam available.' }}
                                </p>
                            </div>
                            <input type="hidden" name="slot_id" id="slotIdInput" value="{{ old('slot_id') }}" {{ $hasSlots ? 'required' : '' }}>
                            @if($isPremarital)
                                <p class="font-caption text-caption text-on-surface-variant leading-relaxed">
                                    Booking sesi berikutnya (pasangan / couple) wajib dengan dokter yang sama.
                                </p>
                            @else
                                <p class="font-caption text-caption text-on-surface-variant leading-relaxed">
                                    Satu kasus bisa membutuhkan lebih dari satu pertemuan — tim YFD akan menjelaskan rencana sesi setelah screening.
                                </p>
                            @endif
                            <p class="font-caption text-caption text-on-surface-variant leading-relaxed">
                                Booking paling lambat H-1 (24 jam sebelum sesi) agar planner sempat pelajari kasus dan siapkan materi.
                            </p>
                        </div>
                    </div>

                    @if(!$isRecovery && !$isPremarital)
                    <div>
                        <label class="font-label-md text-label-md text-on-surface-variant block mb-3">Tahap Finansial (hasil FHCU)</label>
                        <select name="stage" id="stageSelect" class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                            <option value="">— otomatis dari FHCU (email) —</option>
                            @foreach($consultationTiers as $stageKey => $tier)
                                <option value="{{ $stageKey }}"
                                        data-range="{{ ConsultationPricing::formatRange($tier) }}"
                                        {{ ($selectedStage ?? '') === $stageKey || old('stage') === $stageKey ? 'selected' : '' }}>
                                    {{ $tier['label'] }} — {{ ConsultationPricing::formatRange($tier) }}{{ $consultationMeta['period'] ?? '/sesi' }}
                                </option>
                            @endforeach
                        </select>
                        <p id="stageFeeHint" class="font-caption text-caption text-on-surface-variant mt-2 hidden"></p>
                        <p class="font-caption text-caption text-on-surface-variant mt-1">
                            Tarif final mengikuti tahap dari FHCU email Anda (berlaku ≤3 bulan).
                        </p>
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
                            <label for="bf-email" class="font-label-md text-label-md text-on-surface-variant">Email (sama dengan FHCU) *</label>
                            <input id="bf-email" type="email" name="email" required value="{{ old('email') }}" placeholder="nama@email.com"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="bf-phone" class="font-label-md text-label-md text-on-surface-variant">No. WhatsApp (opsional)</label>
                            <input id="bf-phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="08…"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                        <div class="space-y-2">
                            <label for="bf-age" class="font-label-md text-label-md text-on-surface-variant">Usia (opsional)</label>
                            <input id="bf-age" type="number" name="age" min="15" max="99" value="{{ old('age') }}" placeholder="contoh: 28"
                                   class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="bf-cond" class="font-label-md text-label-md text-on-surface-variant">
                            @if($isPremarital)
                                Catatan untuk Tim YFD (opsional)
                            @elseif($isRecovery)
                                Ceritakan situasi Anda (opsional)
                            @else
                                Tujuan / Keluhan Finansial Singkat
                            @endif
                        </label>
                        <textarea id="bf-cond" name="condition" rows="4"
                                  placeholder="{{ $isPremarital ? 'Contoh: Nama pasangan, rencana nikah…' : ($isRecovery ? 'Contoh: Hutang pinjol menumpuk…' : 'Contoh: Fokus perbaikan cashflow…') }}"
                                  class="w-full border-outline-variant rounded-lg focus:ring-primary-container focus:border-primary-container bg-surface">{{ old('condition') }}</textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-outline-variant">
                        <button type="submit" {{ $hasSlots ? '' : 'disabled' }}
                                class="bg-primary-container text-on-primary px-10 py-4 rounded-lg font-label-md text-label-md flex items-center justify-center gap-3 hover:opacity-90 transition-opacity shadow-md disabled:opacity-50">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">payments</span>
                            Bayar &amp; Kunci Jadwal
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
                        @if($isPremarital)
                            Pilih dokter dulu, lalu jadwal. Dokter yang sama menangani semua sesi pasangan.
                        @else
                            Pilih jadwal available, lalu bayar via Pivot. Setelah LUNAS, sistem mengunci slot dan memberi tahu admin.
                        @endif
                    </p>
                </div>
                @if($isPremarital)
                <a href="{{ route('company.bundle.premarital') }}" class="block text-center py-3 rounded-lg bg-secondary-container text-on-secondary-container font-label-md text-label-md hover:brightness-105 transition-all">
                    Pelajari Premarital Plan
                </a>
                @elseif(!$isRecovery)
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
    const isPremarital = @json($isPremarital ?? false);
    const datesByAdvisor = @json($datesByAdvisor ?? []);
    const slotsByAdvisorDate = @json($slotsByAdvisorDate ?? []);
    let availableDates = @json($availableDates ?? []);
    let slotsByDate = @json($slotsByDate ?? []);
    const slotsUrl = @json(route('company.pertemuan.slots'));
    const startMonthStr = @json($calendarMonth ?? now()->format('Y-m'));

    const calDays = document.getElementById('calDays');
    const calMonthLabel = document.getElementById('calMonthLabel');
    const calPrev = document.getElementById('calPrev');
    const calNext = document.getElementById('calNext');
    const slotGrid = document.getElementById('slotGrid');
    const slotIdInput = document.getElementById('slotIdInput');
    const selectedDateLabel = document.getElementById('selectedDateLabel');
    const stageSelect = document.getElementById('stageSelect');
    const stageFeeHint = document.getElementById('stageFeeHint');
    const advisorSelect = document.getElementById('advisorSelect');
    const advisorIdInput = document.getElementById('advisorIdInput');
    const calendarBlock = document.getElementById('calendarBlock');

    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const dowNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    let availableSet = new Set(availableDates);
    let viewYear, viewMonth;
    let selectedDate = null;
    let selectedAdvisorId = '';

    (function initMonth() {
        const parts = String(startMonthStr || '').split('-');
        viewYear = parseInt(parts[0], 10) || new Date().getFullYear();
        viewMonth = (parseInt(parts[1], 10) || (new Date().getMonth() + 1)) - 1;
    })();

    function pad2(n) { return String(n).padStart(2, '0'); }
    function ymd(y, m, d) { return y + '-' + pad2(m + 1) + '-' + pad2(d); }

    function formatIdDate(iso) {
        const p = iso.split('-');
        const y = parseInt(p[0], 10);
        const m = parseInt(p[1], 10) - 1;
        const d = parseInt(p[2], 10);
        const dt = new Date(y, m, d);
        return dowNames[dt.getDay()] + ', ' + d + ' ' + monthNames[m] + ' ' + y;
    }

    function monthBounds() {
        const keys = availableDates.slice().sort();
        if (!keys.length) return { min: null, max: null };
        return {
            min: keys[0].slice(0, 7),
            max: keys[keys.length - 1].slice(0, 7)
        };
    }

    function updateNav() {
        const b = monthBounds();
        const cur = viewYear + '-' + pad2(viewMonth + 1);
        if (calPrev) calPrev.disabled = !b.min || cur <= b.min;
        if (calNext) calNext.disabled = !b.max || cur >= b.max;
    }

    function clearSlots(msg) {
        if (!slotGrid) return;
        slotGrid.innerHTML = '<p class="text-sm text-on-surface-variant">' + (msg || 'Tidak ada slot.') + '</p>';
        if (slotIdInput) slotIdInput.value = '';
        if (selectedDateLabel) selectedDateLabel.textContent = '';
    }

    function renderSlots(date, slots) {
        if (selectedDateLabel) selectedDateLabel.textContent = formatIdDate(date);
        if (!slots || !slots.length) {
            clearSlots('Tidak ada jam available di tanggal ini.');
            return;
        }
        slotGrid.innerHTML = '';
        slots.forEach(function (s) {
            const label = document.createElement('label');
            label.className = 'slot-chip cursor-pointer';
            label.innerHTML =
                '<input type="radio" name="_slot_choice" value="' + s.id + '" class="sr-only">' +
                '<span class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors">' +
                s.label +
                '</span>';
            label.querySelector('input').addEventListener('change', function () {
                slotIdInput.value = s.id;
            });
            slotGrid.appendChild(label);
        });
    }

    function cachedSlotsFor(date) {
        if (isPremarital && selectedAdvisorId) {
            return (slotsByAdvisorDate[selectedAdvisorId] || {})[date] || null;
        }
        return slotsByDate[date] || null;
    }

    function storeCachedSlots(date, slots) {
        if (isPremarital && selectedAdvisorId) {
            if (!slotsByAdvisorDate[selectedAdvisorId]) slotsByAdvisorDate[selectedAdvisorId] = {};
            slotsByAdvisorDate[selectedAdvisorId][date] = slots;
            return;
        }
        slotsByDate[date] = slots;
    }

    function loadSlots(date) {
        if (isPremarital && !selectedAdvisorId) {
            clearSlots('Pilih dokter dulu.');
            return;
        }
        selectedDate = date;
        renderCalendar();
        const cached = cachedSlotsFor(date);
        if (cached && cached.length) {
            renderSlots(date, cached);
            return;
        }
        clearSlots('Memuat jam…');
        let url = slotsUrl + '?date=' + encodeURIComponent(date);
        if (isPremarital && selectedAdvisorId) {
            url += '&advisor_id=' + encodeURIComponent(selectedAdvisorId);
        }
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const slots = data.slots || [];
                storeCachedSlots(date, slots);
                renderSlots(date, slots);
            })
            .catch(function () {
                clearSlots('Gagal memuat jam. Refresh halaman.');
            });
    }

    function renderCalendar() {
        if (!calDays || !calMonthLabel) return;
        calMonthLabel.textContent = monthNames[viewMonth] + ' ' + viewYear;
        updateNav();
        calDays.innerHTML = '';

        const first = new Date(viewYear, viewMonth, 1);
        const startPad = first.getDay();
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        for (let i = 0; i < startPad; i++) {
            const empty = document.createElement('div');
            empty.className = 'cal-cell is-empty';
            empty.setAttribute('aria-hidden', 'true');
            calDays.appendChild(empty);
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const iso = ymd(viewYear, viewMonth, d);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cal-cell';
            btn.setAttribute('aria-label', formatIdDate(iso));

            const available = availableSet.has(iso);
            if (selectedDate === iso) {
                btn.classList.add('is-selected');
            } else if (available) {
                btn.classList.add('is-available');
            }

            btn.innerHTML = '<span>' + d + '</span><span class="cal-dot"></span>';

            if (available) {
                btn.addEventListener('click', function () {
                    loadSlots(iso);
                });
            } else {
                btn.disabled = true;
            }
            calDays.appendChild(btn);
        }
    }

    function applyAdvisorDates() {
        selectedDate = null;
        if (!isPremarital) return;
        selectedAdvisorId = advisorSelect ? String(advisorSelect.value || '') : '';
        if (advisorIdInput) advisorIdInput.value = selectedAdvisorId;
        if (!selectedAdvisorId) {
            availableDates = [];
            availableSet = new Set();
            if (calendarBlock) calendarBlock.classList.add('opacity-60');
            clearSlots('Pilih dokter dulu, lalu tanggal.');
            renderCalendar();
            return;
        }
        availableDates = datesByAdvisor[selectedAdvisorId] || [];
        availableSet = new Set(availableDates);
        if (calendarBlock) calendarBlock.classList.remove('opacity-60');
        if (availableDates.length) {
            const pick = availableDates[0];
            const p = pick.split('-');
            viewYear = parseInt(p[0], 10);
            viewMonth = parseInt(p[1], 10) - 1;
            loadSlots(pick);
        } else {
            clearSlots('Dokter ini belum punya slot open.');
            renderCalendar();
        }
    }

    if (calPrev) {
        calPrev.addEventListener('click', function () {
            viewMonth -= 1;
            if (viewMonth < 0) { viewMonth = 11; viewYear -= 1; }
            renderCalendar();
        });
    }
    if (calNext) {
        calNext.addEventListener('click', function () {
            viewMonth += 1;
            if (viewMonth > 11) { viewMonth = 0; viewYear += 1; }
            renderCalendar();
        });
    }

    function updateStageHint() {
        if (!stageSelect || !stageFeeHint) return;
        const opt = stageSelect.options[stageSelect.selectedIndex];
        if (!opt || !opt.value) {
            stageFeeHint.classList.add('hidden');
            return;
        }
        stageFeeHint.textContent = 'Tarif sesi: ' + (opt.dataset.range || '-') + '.';
        stageFeeHint.classList.remove('hidden');
    }
    if (stageSelect) {
        stageSelect.addEventListener('change', updateStageHint);
        updateStageHint();
    }

    if (isPremarital && advisorSelect) {
        advisorSelect.addEventListener('change', applyAdvisorDates);
        applyAdvisorDates();
    } else {
        renderCalendar();
        if (availableDates.length) {
            let pick = availableDates.find(function (d) {
                return d.indexOf(viewYear + '-' + pad2(viewMonth + 1)) === 0;
            }) || availableDates[0];
            const p = pick.split('-');
            viewYear = parseInt(p[0], 10);
            viewMonth = parseInt(p[1], 10) - 1;
            loadSlots(pick);
        }
    }

    const form = document.getElementById('bookingForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (isPremarital && (!advisorIdInput || !advisorIdInput.value)) {
                e.preventDefault();
                alert('Pilih dokter terlebih dahulu.');
                return;
            }
            if (!slotIdInput || !slotIdInput.value) {
                e.preventDefault();
                alert(isPremarital
                    ? 'Pilih dokter, tanggal, dan jam available terlebih dahulu.'
                    : 'Pilih tanggal dan jam available terlebih dahulu.');
            }
        });
    }
})();
</script>
@endpush

@endsection
