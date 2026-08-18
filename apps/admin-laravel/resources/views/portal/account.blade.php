@extends('portal.layouts.app')

@php
    $privacy = $privacy ?? config('portal_privacy', []);
    $guide = $guide ?? config('portal_guide', []);
@endphp

@section('title', 'Akun, privacy dan panduan — YFD')
@section('heading', 'Akun, privacy dan panduan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <nav class="flex flex-wrap gap-2 text-xs font-semibold">
        <a href="#akun" class="rounded-full bg-navy-800 text-white px-3 py-1.5">Akun</a>
        <a href="#privasi" class="rounded-full bg-slate-100 text-navy-800 px-3 py-1.5 hover:bg-slate-200">Kebijakan privasi</a>
        <a href="#panduan" class="rounded-full bg-slate-100 text-navy-800 px-3 py-1.5 hover:bg-slate-200">Panduan First Aid</a>
        <a href="#faq" class="rounded-full bg-slate-100 text-navy-800 px-3 py-1.5 hover:bg-slate-200">FAQ</a>
    </nav>

    <div id="akun" class="rounded-2xl border bg-white p-6 space-y-2">
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

    <div id="privasi" class="rounded-2xl border bg-white p-6 space-y-4">
        <h2 class="text-base font-extrabold text-navy-800">{{ $privacy['title'] ?? 'Kebijakan privasi' }}</h2>
        <p class="text-xs text-slate-500">Versi {{ $privacy['version'] ?? '1.1' }} · diperbarui {{ $privacy['updated_at'] ?? '' }}</p>
        <p class="text-sm text-slate-700 leading-relaxed">{{ $privacy['intro'] ?? '' }}</p>
        @foreach(($privacy['sections'] ?? []) as $section)
            <div>
                <h3 class="text-sm font-bold text-navy-800 mb-1">{{ $section['heading'] }}</h3>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $section['body'] }}</p>
            </div>
        @endforeach
        <p class="text-sm text-slate-600 border-t pt-4">
            Permintaan akses, koreksi, hapus data, atau tarik persetujuan: WhatsApp Admin YFD
            <strong>{{ $privacy['contact_wa'] ?? '+62 851-1122-8911' }}</strong>.
        </p>
    </div>

    <div id="panduan" class="rounded-2xl border bg-white p-6 space-y-4">
        <h2 class="text-base font-extrabold text-navy-800">Panduan First Aid</h2>
        <p class="text-sm text-slate-600">Buka bolak-balik kapan saja. Ini versi lengkap yang sama dipakai sebagai acuan bot.</p>
        <ol class="space-y-4">
            @foreach(($guide['topics'] ?? []) as $topic)
                <li id="{{ $topic['id'] }}">
                    <h3 class="text-sm font-bold text-navy-800 mb-1">{{ $topic['title'] }}</h3>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $topic['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>

    <div id="faq" class="rounded-2xl border bg-white p-6 space-y-4">
        <h2 class="text-base font-extrabold text-navy-800">FAQ</h2>
        @foreach(($guide['faq'] ?? []) as $item)
            <details class="rounded-xl border border-slate-200 px-4 py-3">
                <summary class="text-sm font-semibold text-navy-800 cursor-pointer">{{ $item['q'] }}</summary>
                <p class="text-sm text-slate-700 leading-relaxed mt-2">{{ $item['a'] }}</p>
            </details>
        @endforeach
    </div>
</div>
@endsection
