@extends('Companyprofile.layouts.main')

@section('title', "Checkout — {$product->name}")
@section('description', "Selesaikan pembelian {$product->name}. Pembayaran aman lewat Midtrans (kartu, VA, e-wallet, QRIS).")

@section('content')

<section class="bg-surface-container-low border-b border-outline-variant">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12">
        <nav class="text-[12.5px] text-on-surface-variant mb-3">
            <a href="{{ route('company.produk') }}" class="hover:text-primary">Produk</a>
            <span class="mx-2">/</span>
            <span class="text-primary font-semibold">Checkout</span>
        </nav>
        <h1 class="font-heading text-headline-lg text-primary">Checkout</h1>
        <p class="text-body-md text-on-surface-variant mt-1">Lengkapi data Anda. Setelah submit, Anda diarahkan ke halaman pembayaran Midtrans.</p>
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
                <p class="text-[13px] text-on-surface-variant mb-6">Email Gmail wajib diisi — setelah pembayaran lunas, kami kirim ke email tersebut: <strong>tautan bot Telegram</strong>, <strong>kode /activate</strong>, dan <strong>link Google Sheet</strong>. Kode aktivasi <strong>tidak ditampilkan di website</strong>, hanya lewat email. Nomor WhatsApp untuk kontak darurat.</p>

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
                            <label class="block text-[12.5px] font-semibold text-on-surface mb-1.5">Email Gmail (akses Google Sheet) <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required maxlength="190"
                                   value="{{ old('email') }}"
                                   class="w-full rounded-xl border-outline-variant focus:border-primary focus:ring-primary text-[14px] @error('email') border-red-400 @enderror"
                                   placeholder="nama@gmail.com"
                                   autocomplete="email">
                            <p class="text-[11px] text-on-surface-variant mt-1">Isi Gmail yang akan dipakai membuka spreadsheet — bukan email lain.</p>
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

                    <div>
                        <label class="block text-[12.5px] font-semibold text-on-surface mb-1.5">Username Telegram <span class="text-on-surface-variant font-normal">(opsional, untuk verifikasi bot)</span></label>
                        <input type="text" name="telegram_username" maxlength="120"
                               value="{{ old('telegram_username') }}"
                               class="w-full rounded-xl border-outline-variant focus:border-primary focus:ring-primary text-[14px]"
                               placeholder="@username">
                    </div>
                </div>

                <div class="mt-7 flex flex-wrap gap-3 items-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <span class="material-symbols-outlined text-[20px]">lock</span>
                        Lanjut ke Pembayaran
                    </button>
                    <a href="{{ route('company.produk') }}" class="text-[13px] text-on-surface-variant hover:text-primary">← Kembali ke produk</a>
                </div>
                <p class="text-[11.5px] text-on-surface-variant mt-4">
                    Dengan melanjutkan Anda menyetujui ketentuan layanan YFD. Pembayaran diproses oleh Midtrans (PT Midtrans, lisensi BI).
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
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Harga normal</dt>
                        <dd class="@if($product->on_sale) line-through text-on-surface-variant @else font-semibold @endif">{{ $product->priceLabel($product->price) }}</dd>
                    </div>
                    @if($product->on_sale)
                        <div class="flex justify-between text-emerald-700">
                            <dt>Diskon ({{ $product->discount_percent }}%)</dt>
                            <dd>− {{ $product->priceLabel($product->price - $product->discount_price) }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="border-t border-outline-variant pt-4 flex justify-between items-baseline">
                    <span class="text-[13px] text-on-surface-variant">Total bayar</span>
                    <span class="font-display text-[28px] font-extrabold text-primary-container">{{ $product->priceLabel() }}</span>
                </div>

                <ul class="mt-5 space-y-2 text-[12.5px] text-on-surface-variant">
                    <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">verified</span> Pembayaran via Midtrans (kartu, VA, GoPay, OVO, ShopeePay, QRIS)</li>
                    <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">mail</span> Setelah lunas: <strong>email otomatis</strong> berisi link bot Telegram, kode lisensi, &amp; Google Sheet</li>
                    <li class="flex items-start gap-2"><span class="material-symbols-outlined text-emerald-600 text-[16px]">support_agent</span> Onboarding 1×24 jam oleh tim YFD</li>
                </ul>
            </div>
        </div>
    </form>
</section>

@endsection
