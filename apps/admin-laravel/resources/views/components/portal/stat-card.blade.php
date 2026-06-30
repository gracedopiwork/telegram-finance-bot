@props(['label', 'value', 'hint' => null, 'icon' => 'payments', 'tone' => 'navy'])

@php
    $tones = [
        'emerald' => 'text-emerald-600 bg-emerald-50',
        'rose' => 'text-rose-600 bg-rose-50',
        'navy' => 'text-navy-800 bg-slate-50',
        'gold' => 'text-amber-700 bg-amber-50',
    ];
    $iconTone = $tones[$tone] ?? $tones['navy'];
@endphp
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</div>
            <div class="text-xl sm:text-2xl font-extrabold text-navy-800 mt-1 break-words">{{ $value }}</div>
            @if($hint)
                <div class="text-xs text-slate-500 mt-1.5">{{ $hint }}</div>
            @endif
        </div>
        <div class="w-10 h-10 rounded-xl {{ $iconTone }} flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined">{{ $icon }}</span>
        </div>
    </div>
</div>
