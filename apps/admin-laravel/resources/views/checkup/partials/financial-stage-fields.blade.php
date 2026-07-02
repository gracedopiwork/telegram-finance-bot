@php
    $fs = $config['financial_stage'] ?? [];
    $currentSection = '';
@endphp

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="bg-primary text-on-primary px-5 py-4">
        <h2 class="font-bold text-lg flex items-center gap-2">
            <span class="material-symbols-outlined">monitor_heart</span>
            Financial Health Check-Up
        </h2>
        <p class="text-white/80 text-sm mt-1">Jawab sesuai kondisi Anda saat ini — gratis, tanpa login.</p>
    </div>
    <div class="p-5 sm:p-6 space-y-8">
        @foreach($fs['profile'] ?? [] as $q)
            @if($currentSection !== $q['section'])
                @php $currentSection = $q['section']; @endphp
                <h3 class="text-xs font-bold uppercase tracking-wider text-primary border-b pb-2">{{ $q['section'] }}</h3>
            @endif
            <fieldset class="space-y-3">
                <legend class="font-semibold text-slate-800 text-sm">{{ $q['text'] }}</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($q['options'] as $value => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input type="radio" name="fs[{{ $q['key'] }}]" value="{{ $value }}"
                                   class="text-primary" @checked(old("fs.{$q['key']}") === $value) required>
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error("fs.{$q['key']}")
                    <p class="text-rose-600 text-xs">{{ $message }}</p>
                @enderror
            </fieldset>
        @endforeach

        @php $currentSection = ''; @endphp
        @foreach($fs['scored'] ?? [] as $q)
            @if($currentSection !== $q['section'])
                @php $currentSection = $q['section']; @endphp
                <h3 class="text-xs font-bold uppercase tracking-wider text-primary border-b pb-2 mt-4">{{ $q['section'] }}</h3>
            @endif
            <fieldset class="space-y-3">
                <legend class="font-semibold text-slate-800 text-sm">{{ $q['text'] }}</legend>
                <div class="grid gap-2">
                    @foreach($q['options'] as $value => $opt)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input type="radio" name="fs[{{ $q['key'] }}]" value="{{ $value }}"
                                   class="text-primary shrink-0" @checked(old("fs.{$q['key']}") === $value) required>
                            <span class="text-sm">{{ $opt['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error("fs.{$q['key']}")
                    <p class="text-rose-600 text-xs">{{ $message }}</p>
                @enderror
            </fieldset>
        @endforeach
    </div>
</div>
