@extends('portal.layouts.app')

@section('title', 'Referral & Komisi')
@section('heading', 'Referral YFD First Aid')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border bg-white p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-bold">Kode referral Anda</div>
            <div class="mt-2 text-2xl font-extrabold text-navy-800 tracking-wide">{{ $affiliate->referral_code }}</div>
            <p class="text-xs text-slate-500 mt-2">Bagikan ke teman yang beli YFD First Aid.</p>
        </div>
        <div class="rounded-2xl border bg-white p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-bold">Saldo tersedia</div>
            <div class="mt-2 text-2xl font-extrabold text-emerald-700">Rp {{ number_format($balance, 0, ',', '.') }}</div>
            <p class="text-xs text-slate-500 mt-2">Komisi Rp {{ number_format($commissionAmount, 0, ',', '.') }} / referral sukses.</p>
        </div>
        <div class="rounded-2xl border bg-white p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-bold">Referral masuk</div>
            <div class="mt-2 text-2xl font-extrabold text-navy-800">{{ $referralCount }}</div>
            <p class="text-xs text-slate-500 mt-2">Orang yang pakai kode {{ $affiliate->referral_code }}.</p>
        </div>
    </div>

    <div class="rounded-2xl border bg-white p-5">
        <div class="text-sm font-bold text-navy-800 mb-2">Link bagikan</div>
        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" readonly value="{{ $shareUrl }}"
                   class="flex-1 rounded-xl border-slate-200 text-sm bg-slate-50"
                   onclick="this.select()">
            <button type="button"
                    class="btn btn-primary justify-center"
                    onclick="navigator.clipboard.writeText(@js($shareUrl))">
                Salin link
            </button>
        </div>
        <p class="text-xs text-slate-500 mt-2">
            Diskon teman: Rp {{ number_format($discountAmount, 0, ',', '.') }} di checkout bila kode dipakai.
        </p>
    </div>

    <div class="rounded-2xl border bg-white overflow-hidden">
        <div class="px-5 py-4 border-b font-bold text-navy-800 text-sm flex items-center justify-between gap-2">
            <span>Orang yang Anda referral</span>
            <span class="text-xs font-semibold text-slate-500">{{ $referralCount }} orang</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Nama / Email</th>
                        <th class="text-left px-4 py-3">Produk</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($referrals as $ref)
                        <tr class="border-t">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-navy-800">{{ $ref->full_name ?: '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $ref->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                {{ $ref->digitalProduct?->name ?? $ref->product_name ?? $ref->plan ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @php [$lbl] = $ref->statusBadge(); @endphp
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                    {{ $lbl }}
                                </span>
                                @if($ref->isAdminComplimentary())
                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-700 ml-1">
                                        Admin
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ ($ref->paid_at ?? $ref->created_at)?->format('d M Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                Belum ada orang yang memakai kode Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border bg-white p-5">
        <div class="flex items-center justify-between gap-3 mb-3">
            <div>
                <div class="text-sm font-bold text-navy-800">Ajukan klaim pencairan</div>
                <p class="text-xs text-slate-500 mt-1">
                    Minimal Rp {{ number_format($minClaim, 0, ',', '.') }}.
                    Pajak: {{ rtrim(rtrim(number_format($taxWithNpwp, 2, '.', ''), '0'), '.') }}% (ada NPWP) /
                    {{ rtrim(rtrim(number_format($taxWithoutNpwp, 2, '.', ''), '0'), '.') }}% (tanpa NPWP).
                    Wajib isi rekening tujuan transfer. Admin proses manual setelah disetujui.
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('portal.affiliate.claim') }}" class="space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama bank <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $affiliate->bank_name) }}" required maxlength="80"
                           class="w-full rounded-xl border-slate-200 text-sm" placeholder="Contoh: BCA / Mandiri / BNI">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nomor rekening <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $affiliate->bank_account_number) }}" required maxlength="64"
                           class="w-full rounded-xl border-slate-200 text-sm" placeholder="Hanya angka">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama pemilik rekening <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $affiliate->bank_account_name) }}" required maxlength="120"
                           class="w-full rounded-xl border-slate-200 text-sm" placeholder="Sesuai buku tabungan">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">NPWP (opsional)</label>
                    <input type="text" name="npwp" value="{{ old('npwp', $affiliate->npwp) }}" maxlength="32"
                           class="w-full rounded-xl border-slate-200 text-sm" placeholder="Opsional — memengaruhi % pajak klaim">
                </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-slate-500">Data rekening tersimpan untuk klaim berikutnya.</p>
                <button type="submit" class="btn btn-primary"
                        {{ $balance < $minClaim ? 'disabled' : '' }}
                        onclick="return confirm('Ajukan klaim seluruh saldo ke rekening ini?')">
                    Klaim saldo
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border bg-white overflow-hidden">
        <div class="px-5 py-4 border-b font-bold text-navy-800 text-sm">Riwayat komisi</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Tanggal</th>
                        <th class="text-left px-4 py-3">Order</th>
                        <th class="text-right px-4 py-3">Komisi</th>
                        <th class="text-left px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $c)
                        <tr class="border-t">
                            <td class="px-4 py-3">{{ $c->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $c->order?->order_code ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($c->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $c->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada komisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border bg-white overflow-hidden">
        <div class="px-5 py-4 border-b font-bold text-navy-800 text-sm">Riwayat klaim</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Tanggal</th>
                        <th class="text-right px-4 py-3">Gross</th>
                        <th class="text-right px-4 py-3">Pajak</th>
                        <th class="text-right px-4 py-3">Net</th>
                        <th class="text-left px-4 py-3">Rekening</th>
                        <th class="text-left px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                        <tr class="border-t">
                            <td class="px-4 py-3">{{ $claim->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($claim->gross_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($claim->tax_amount, 0, ',', '.') }} ({{ $claim->tax_percent }}%)</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($claim->net_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                @if($claim->bank_name)
                                    <div class="font-semibold text-slate-800">{{ $claim->bank_name }}</div>
                                    <div>{{ $claim->bank_account_number }}</div>
                                    <div>{{ $claim->bank_account_name }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $claim->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada klaim.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
