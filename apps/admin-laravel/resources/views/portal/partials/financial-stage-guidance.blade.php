@php
    $stageGuidance = $stageGuidance ?? [];
    $hasContent = trim((string) ($stageGuidance['summary'] ?? '')) !== ''
        || ! empty($stageGuidance['therapy_plan'])
        || ! empty($stageGuidance['doctor_notes']);
@endphp

@if($hasContent)
<div class="mt-4 space-y-4 border-t border-slate-100 pt-4">
    @if(trim((string) ($stageGuidance['summary'] ?? '')) !== '')
        <p class="text-sm text-slate-600 leading-relaxed">{{ $stageGuidance['summary'] }}</p>
    @endif

    @if(!empty($stageGuidance['therapy_plan']))
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-2">Rencana terapi keuangan</div>
            <ol class="list-decimal pl-5 space-y-1.5 text-sm text-slate-700">
                @foreach($stageGuidance['therapy_plan'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
            @if(trim((string) ($stageGuidance['bridge'] ?? '')) !== '')
                <p class="text-xs text-slate-500 mt-2 italic">{{ $stageGuidance['bridge'] }}</p>
            @endif
        </div>
    @endif

    @if(trim((string) ($stageGuidance['targets']['3m'] ?? '')) !== '' || trim((string) ($stageGuidance['targets']['12m'] ?? '')) !== '')
        <div class="grid sm:grid-cols-2 gap-3 text-sm">
            @if(trim((string) ($stageGuidance['targets']['3m'] ?? '')) !== '')
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-[10px] font-bold uppercase text-slate-500">Target 3 bulan</div>
                    <p class="mt-1 text-slate-700">{{ $stageGuidance['targets']['3m'] }}</p>
                </div>
            @endif
            @if(trim((string) ($stageGuidance['targets']['12m'] ?? '')) !== '')
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-[10px] font-bold uppercase text-slate-500">Target 12 bulan</div>
                    <p class="mt-1 text-slate-700">{{ $stageGuidance['targets']['12m'] }}</p>
                </div>
            @endif
        </div>
    @endif

    @if(!empty($stageGuidance['doctor_notes']))
        <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
            <div class="text-[11px] font-bold uppercase tracking-wide text-emerald-800 mb-2">Catatan dokter finansial</div>
            <ul class="space-y-1.5 text-sm text-emerald-900">
                @foreach($stageGuidance['doctor_notes'] as $note)
                    <li class="flex gap-2"><span>✔</span><span>{{ $note }}</span></li>
                @endforeach
            </ul>
            @include('portal.partials.ai-guidance-disclaimer', ['extraClass' => 'mt-3 border-t border-emerald-100 pt-3 text-emerald-800/80'])
        </div>
    @endif

    @if(($stageGuidance['source'] ?? '') === 'ai')
        <p class="text-[10px] text-slate-400">Dipersonalisasi dari hasil check-up · {{ $stageGuidance['generated_at'] ? \Carbon\Carbon::parse($stageGuidance['generated_at'])->timezone('Asia/Jakarta')->format('d M Y H:i') : '' }} WIB</p>
    @endif
</div>
@endif
