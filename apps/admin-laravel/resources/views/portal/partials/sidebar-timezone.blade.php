@php
    $portalTimezoneMeta = $portalTimezoneMeta ?? ['timezone' => 'Asia/Jakarta', 'label' => 'WIB', 'source' => 'default'];
    $portalTimezoneOptions = $portalTimezoneOptions ?? [];
    $currentZoneKey = null;
    foreach ($portalTimezoneOptions as $key => $opt) {
        if (($opt['name'] ?? '') === ($portalTimezoneMeta['timezone'] ?? '')) {
            $currentZoneKey = $key;
            break;
        }
    }
@endphp
<div class="mx-3 mb-2 rounded-xl bg-white/5 border border-white/10 p-3 text-xs text-white/85">
    <div class="flex items-center gap-2 mb-2 font-semibold text-white/90">
        <span class="material-symbols-outlined text-sm">schedule</span>
        Zona Waktu
        <span id="portal-tz-badge" class="ml-auto text-[10px] bg-gold-400/20 text-gold-300 px-1.5 py-0.5 rounded font-bold">
            {{ $portalTimezoneMeta['label'] ?? 'WIB' }}
        </span>
    </div>
    <p class="text-white/55 mb-2 leading-relaxed">
        Jam transaksi ditampilkan sesuai zona Anda.
        @if(($portalTimezoneMeta['source'] ?? '') === 'auto')
            <span class="text-emerald-300">(otomatis dari perangkat)</span>
        @elseif(($portalTimezoneMeta['source'] ?? '') === 'manual')
            <span class="text-sky-300">(dipilih manual)</span>
        @endif
    </p>
    <form method="post" action="{{ route('portal.timezone.manual') }}" class="space-y-2">
        @csrf
        <select name="zone"
                class="w-full rounded-lg border-white/20 bg-navy-900/60 text-white text-xs py-2 px-2 focus:ring-gold-400 focus:border-gold-400">
            @foreach($portalTimezoneOptions as $key => $opt)
                <option value="{{ $key }}" @selected($currentZoneKey === $key)>
                    {{ $opt['label'] }} — {{ $opt['desc'] }}
                </option>
            @endforeach
        </select>
        <button type="submit"
                class="w-full rounded-lg bg-white/10 hover:bg-white/15 text-white text-xs font-semibold py-2 transition-colors">
            Simpan zona waktu
        </button>
    </form>
</div>
