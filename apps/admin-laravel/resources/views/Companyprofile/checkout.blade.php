@extends('Companyprofile.layouts.main')

@section('title', "Checkout — {$product->name}")
@section('description', "Selesaikan pembelian {$product->name}. Pembayaran aman lewat Pivot.")

@section('content')

<section class="bg-surface-container-low border-b border-outline-variant">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12">
        <nav class="text-[12.5px] text-on-surface-variant mb-3">
            <a href="{{ route('company.produk') }}" class="hover:text-primary">Produk</a>
            <span class="mx-2">/</span>
            <span class="text-primary font-semibold">Checkout</span>
        </nav>
        <h1 class="font-heading text-headline-lg text-primary">Checkout</h1>
        <p class="text-body-md text-on-surface-variant mt-1">Lengkapi data Anda. Setelah submit, Anda diarahkan ke halaman pembayaran Pivot.</p>
    </div>
</section>

<section class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12">
    <form action="{{ route('checkout.store') }}" method="POST" class="grid lg:grid-cols-12 gap-8">
        @csrf
        <input type="hidden" name="product" value="{{ $product->code }}">

        {{-- Form data customer --}}
        <div class="lg:col-span-7">
            <div class="bg-white rounded-2xl border border-outline-variant shadow-soft p-7 md:p-9">
                <h2 class="font-heading text-[20px] font-bold text-primary mb-1">Data Pembeli</h2>
                <p class="text-[13px] text-on-surface-variant mb-6">
                    @if($product->code === 'yfd-ftsa-premium')
                        Email wajib diisi — gunakan <strong>email yang sama</strong> dengan akun YFD First Aid jika ini upgrade. Paket FTSA membuka <strong>dashboard FTSA saja</strong> (12 bulan evaluasi), bukan YFD First Aid.
                    @else
                        Email wajib diisi — setelah pembayaran lunas, kami kirim ke email tersebut: <strong>tautan YFD First Aid</strong>, <strong>kode /activate</strong>, dan <strong>akses dashboard web</strong>. Pembelian mencakup <strong>biaya admin 1 tahun</strong>; tahun berikutnya perpanjang Rp10.000/bulan atau Rp99.000/tahun. Kode aktivasi <strong>tidak ditampilkan di website</strong>, hanya lewat email. Nomor WhatsApp untuk kontak darurat.
                    @endif
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[12.5px] font-semibold text-on-surface mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" required maxlength="120"
                               value="{{ old('full_name') }}"
                               class="w-full rounded-xl border-outline-variant focus:border-primary focus:ring-primary text-[14px] @error('full_name') border-red-400 @enderror"
                               placeholder="Nama sesuai KTP / nama panggilan">
                        @error('full_name') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12.5px] font-semibold text-on-surface mb-1.5">Email aktif <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required maxlength="190"
                                   value="{{ old('email') }}"
                                   class="w-full rounded-xl border-outline-variant focus:border-primary focus:ring-primary text-[14px] @error('email') border-red-400 @enderror"
                                   placeholder="nama@gmail.com"
                                   autocomplete="email">
                            <p class="text-[11px] text-on-surface-variant mt-1">Gunakan email yang akan dipakai login dashboard web YFD.</p>
                            @error('email') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-semibold text-on-surface mb-1.5">No. Telepon / WhatsApp <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" required maxlength="32"
                                   value="{{ old('phone') }}"
                                   class="w-full rounded-xl border-outline-variant focus:border-primary focus:ring-primary text-[14px] @error('phone') border-red-400 @enderror"
                                   placeholder="08xxxxxxxxxx">
                            @error('phone') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if($product->code !== 'yfd-ftsa-premium')
                    <div>
                        <label class="block text-[12.5px] font-semibold text-on-surface mb-1.5">Username Telegram <span class="text-on-surface-variant font-normal">(opsional, untuk verifikasi bot)</span></label>
                        <input type="text" name="telegram_username" maxlength="120"
                               value="{{ old('telegram_username') }}"
                               class="w-full rounded-xl border-outline-variant focus:border-primary focus:ring-primary text-[14px]"
                               placeholder="@username">
                    </div>
                    @endif

                    @if($referralEnabled ?? false)
                    <div>
                        <label class="block text-[12.5px] font-semibold text-on-surface mb-1.5">Kode Referral <span class="text-on-surface-variant font-normal">(opsional)</span></label>
                        <input type="text" name="referral_code" maxlength="32"
                               value="{{ old('referral_code', $prefillReferral ?? '') }}"
                               class="w-full rounded-xl border-outline-variant focus:border-primary focus:ring-primary text-[14px] uppercase @error('referral_code') border-red-400 @enderror"
                               placeholder="YFD-XXXXXX"
                               autocomplete="off">
                        <p class="text-[11px] text-on-surface-variant mt-1">
                            Pakai kode teman → potongan tambahan
                            <strong>Rp {{ number_format($referralDiscount ?? 0, 0, ',', '.') }}</strong>
                            (jika kode valid).
                        </p>
                        @error('referral_code') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endif
                </div>

                <div class="mt-7 flex flex-wrap gap-3 items-center">
                    <a href="{{ route('company.privacy') }}" class="btn btn-outline-primary btn-lg">
                        <span class="material-symbols-outlined text-[20px]">menu_book</span>
                        Baca Kebijakan Lengkap
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <span class="material-symbols-outlined text-[20px]">lock</span>
                        Lanjut ke Pembayaran
                    </button>
                    <a href="{{ route('company.produk') }}" class="text-[13px] text-on-surface-variant hover:text-primary">← Kembali ke produk</a>
                </div>
                <p class="text-[11.5px] text-on-surface-variant mt-4 leading-relaxed">
                    Sebelum kamu lanjut ke pembayaran, ini ringkasan bagaimana YFD First Aid memproses datamu:
                    {{ config('portal_privacy.purchase_summary') }}
                    <a href="{{ route('company.privacy') }}" class="text-primary font-semibold hover:underline">Baca kebijakan privasi lengkap</a>.
                    Pembayaran diproses oleh Pivot Payment.
                </p>
            </div>
        </div>

        {{-- Order summary --}}
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl border border-outline-variant shadow-soft p-7 sticky top-24">
                <h2 class="font-heading text-[18px] font-bold text-primary mb-4">Ringkasan Order</h2>

                <div class="flex items-start gap-4 pb-5 border-b border-outline-variant">
                    <span class="w-12 h-12 rounded-xl bg-primary-container/10 grid place-items-center flex-shrink-0">
                        <span class="material-symbols-outlined text-primary-container">{{ $product->icon }}</span>
                    </span>
                    <div class="flex-1">
                        <div class="font-bold text-primary text-[15px]">{{ $product->name }}</div>
                        @if($product->tagline)
                            <p class="text-[12.5px] text-on-surface-variant mt-0.5">{{ \Illuminate\Support\Str::limit($product->tagline, 90) }}</p>
                        @endif
                        <div class="text-[11.5px] text-on-surface-variant mt-1">{{ $product->period }}</div>
                    </div>
                </div>

                <dl class="text-[14px] py-5 space-y-2.5">
                    @php
                        $listPrice = (int) $product->price;
                        $salePrice = (int) $product->effective_price;
                        $promoOff = max(0, $listPrice - $salePrice);
                        $refDisc = (($referralEnabled ?? false) && ($referralDiscount ?? 0) > 0)
                            ? (int) $referralDiscount
                            : 0;
                        $totalWithReferral = max(0, $salePrice - $refDisc);
                        $isBundle = $product->code === 'yfd-first-aid-ftsa';
                    @endphp
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Harga normal</dt>
                        <dd class="@if($product->on_sale) line-through text-on-surface-variant @else font-semibold @endif">{{ $product->priceLabel($listPrice) }}</dd>
                    </div>
                    @if($product->on_sale)
                        <div class="flex justify-between text-emerald-700">
                            <dt>Diskon ({{ $product->discountPercentLabel() }}%)</dt>
                            <dd>− {{ $product->priceLabel($promoOff) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-on-surface-variant">Harga setelah diskon</dt>
                            <dd class="font-semibold">{{ $product->priceLabel($salePrice) }}</dd>
                        </div>
                    @endif
                    @if($refDisc > 0)
                        <div class="flex justify-between text-emerald-700">
                            <dt>Potongan referral (jika kode valid)</dt>
                            <dd>− Rp {{ number_format($refDisc, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="border-t border-outline-variant pt-4 space-y-2">
                    <div class="flex justify-between items-baseline">
                        <span class="text-[13px] text-on-surface-variant">Total bayar</span>
                        <span class="font-display text-[28px] font-extrabold text-primary-container">{{ $product->priceLabel($salePrice) }}</span>
                    </div>
                    @if($refDisc > 0)
                        <div class="flex justify-between items-baseline text-emerald-800 bg-emerald-50 rounded-xl px-3 py-2">
                            <span class="text-[12.5px] font-semibold">Total dengan kode referral valid</span>
                            <span class="font-display text-[22px] font-extrabold">{{ $product->priceLabel($totalWithReferral) }}</span>
                        </div>
                    @endif
                    @if($isBundle && ($referralEnabled ?? false) && $refDisc > 0)
                        <p class="text-[11.5px] text-on-surface-variant leading-relaxed bg-amber-50 border border-amber-100 rounded-xl px-3 py-2">
                            Bundle First Aid + FTSA: pakai kode referral valid dapat potongan tambahan
                            <strong>Rp {{ number_format($refDisc, 0, ',', '.') }}</strong>
                            (total jadi <strong>{{ $product->priceLabel($totalWithReferral) }}</strong>).
                        </p>
                    @endif
                </div>

                <ul class="mt-5 space-y-2 text-[12.5px] text-on-surface-variant">
                    <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">verified</span> Pembayaran via <strong>QRIS</strong> (scan &amp; bayar di aplikasi bank/e-wallet Anda)</li>
                    @if($product->code === 'yfd-ftsa-premium')
                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">psychology</span> Dashboard FTSA &amp; behavioral aktif <strong>12 bulan evaluasi</strong></li>
                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">info</span> <strong>Tidak termasuk</strong> YFD First Aid &amp; dashboard transaksi</li>
                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">link</span> Upgrade dari bot? Pakai <strong>email &amp; lisensi yang sama</strong></li>
                    @else
                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">mail</span> Setelah lunas: <strong>email otomatis</strong> berisi link YFD First Aid, kode lisensi, &amp; akses dashboard web</li>
                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">schedule</span> Termasuk <strong>biaya admin 1 tahun</strong> — lalu perpanjang Rp10.000/bulan atau Rp99.000/tahun</li>
                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">support_agent</span> Onboarding 1×24 jam oleh tim YFD</li>
                    @endif
                </ul>
            </div>
        </div>
    </form>
</section>

@endsection
