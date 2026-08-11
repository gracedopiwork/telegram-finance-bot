@extends('portal.layouts.app')

@section('title', 'Akun & Password — YFD')
@section('heading', 'Akun & Password')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-2xl border bg-white p-6 space-y-2">
        <div class="text-xs uppercase tracking-wider text-slate-500 font-bold">Email login</div>
        <div class="text-lg font-extrabold text-navy-800">{{ $email }}</div>
        <p class="text-sm text-slate-600 leading-relaxed">
            Setelah password dibuat, login portal bisa pilih <strong>kode lisensi</strong> atau <strong>password</strong>.
            Kode lisensi tetap dipakai untuk aktivasi bot Telegram.
        </p>
    </div>

    <div class="rounded-2xl border bg-white p-6">
        <h2 class="text-base font-extrabold text-navy-800 mb-1">
            {{ $hasPassword ? 'Ganti password' : 'Buat password' }}
        </h2>
        <p class="text-sm text-slate-600 mb-5">
            Minimal 8 karakter. Jangan bagikan password atau kode lisensi saat screenshare.
        </p>

        <form method="post" action="{{ route('portal.account.password') }}" class="space-y-4">
            @csrf
            @if($hasPassword)
                <div>
                    <label class="block text-sm font-semibold text-navy-800 mb-1.5">Password lama</label>
                    <input type="password" name="current_password" required
                           class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:ring-navy-500 focus:border-navy-500"
                           autocomplete="current-password">
                </div>
            @endif
            <div>
                <label class="block text-sm font-semibold text-navy-800 mb-1.5">Password baru</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:ring-navy-500 focus:border-navy-500"
                       autocomplete="new-password">
            </div>
            <div>
                <label class="block text-sm font-semibold text-navy-800 mb-1.5">Ulangi password baru</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:ring-navy-500 focus:border-navy-500"
                       autocomplete="new-password">
            </div>
            <button type="submit" class="w-full rounded-xl bg-navy-800 text-white font-bold py-3 hover:bg-navy-700 transition-colors">
                {{ $hasPassword ? 'Simpan password baru' : 'Buat password' }}
            </button>
        </form>
    </div>
</div>
@endsection
