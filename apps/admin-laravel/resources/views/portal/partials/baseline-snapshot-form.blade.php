@php
    $existingBaseline = $existingBaseline ?? null;
    $snapshotValue = fn (string $field) => old("snapshot.{$field}", $existingBaseline?->{$field});
    $snapshotChecked = fn (string $field) => (bool) old("snapshot.{$field}", $existingBaseline?->{$field});
    $stageLabel = $stageLabel ?? ($existingBaseline?->stage_label ?? null);
@endphp

<div id="baseline-snapshot" class="scroll-mt-24">
    @if($stageLabel)
        <div class="rounded-xl border border-gold-400/50 bg-gold-50 px-4 py-3 mb-4 text-sm text-amber-900">
            <strong>Diagnostik sudah tersimpan</strong> ({{ $stageLabel }}).
            Isi snapshot angka keuangan di bawah lalu klik Simpan.
        </div>
    @endif
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="bg-navy-800 text-white px-5 py-4">
            <h2 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined">inventory_2</span>
                Snapshot Keuangan (Baseline Data)
            </h2>
            <p class="text-white/70 text-sm mt-1">Isi perkiraan terbaik Anda saat ini — langsung di dashboard</p>
        </div>
        <form method="post" action="{{ route('portal.baseline.store') }}" class="p-5 sm:p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-navy-800 mb-1">Current Goal (tujuan finansial saat ini)</label>
                <input type="text" name="snapshot[current_goal]" value="{{ $snapshotValue('current_goal') }}"
                       class="w-full rounded-lg border-slate-300 text-sm" placeholder="Contoh: Dana darurat 6 bulan + lunasi kartu kredit">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach([
                    'avg_monthly_income' => 'Rata-rata pendapatan bulanan (Rp)',
                    'emergency_fund' => 'Dana darurat (Rp)',
                    'cash_savings' => 'Cash / tabungan (Rp)',
                    'total_investment' => 'Total investasi (Rp)',
                    'total_asset' => 'Total aset (Rp)',
                    'total_debt' => 'Total utang (Rp)',
                ] as $field => $label)
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ $label }}</label>
                        <input type="number" name="snapshot[{{ $field }}]" min="0" step="1000"
                               value="{{ $snapshotValue($field) }}"
                               class="w-full rounded-lg border-slate-300 text-sm" placeholder="0">
                    </div>
                @endforeach
            </div>
            <fieldset>
                <legend class="text-sm font-semibold text-navy-800 mb-2">Proteksi yang dimiliki</legend>
                <div class="flex flex-wrap gap-4">
                    @foreach([
                        'has_bpjs' => 'BPJS',
                        'has_health_insurance' => 'Asuransi kesehatan',
                        'has_income_protection' => 'Income protection',
                        'has_life_insurance' => 'Asuransi jiwa',
                    ] as $field => $label)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="snapshot[{{ $field }}]" value="1"
                                   class="rounded border-slate-300 text-navy-600"
                                   @checked($snapshotChecked($field))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-navy-800 hover:bg-navy-700 text-white font-semibold px-6 py-3 rounded-xl shadow-sm">
                <span class="material-symbols-outlined">save</span>
                Simpan Baseline
            </button>
        </form>
    </div>
</div>
