{{-- Branding header Doctor's Note (ikon / foto opsional) --}}
@php
    $doctorName = (string) config('portal.doctors_note.name', 'dr. Financial');
    $doctorTitle = (string) config('portal.doctors_note.title', 'Your Financial Doctor');
    $doctorPhoto = trim((string) config('portal.doctors_note.photo', ''));
    $doctorPhotoUrl = $doctorPhoto !== ''
        ? (str_starts_with($doctorPhoto, 'http') ? $doctorPhoto : asset($doctorPhoto))
        : null;
@endphp
<div class="flex items-center gap-3 {{ $extraClass ?? '' }}">
    @if($doctorPhotoUrl)
        <img src="{{ $doctorPhotoUrl }}" alt="{{ $doctorName }}"
             class="h-11 w-11 rounded-full object-cover border border-slate-200 shrink-0">
    @else
        <div class="h-11 w-11 rounded-full bg-navy-800 text-gold-400 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[26px]">stethoscope</span>
        </div>
    @endif
    <div class="min-w-0">
        <div class="text-sm font-semibold text-navy-800 leading-tight">Doctor's Note</div>
        <div class="text-xs text-slate-500 truncate">{{ $doctorName }} · {{ $doctorTitle }}</div>
    </div>
</div>
