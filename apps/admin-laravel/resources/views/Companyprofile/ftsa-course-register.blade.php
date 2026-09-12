@extends('Companyprofile.layouts.main')

@section('title', 'Daftar FTSA Course — Your Financial Doctor')
@section('description', 'Daftar course FTSA perusahaan dengan kode undangan.')

@section('content')
<section class="bg-surface-container-lowest py-10 md:py-16">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <div class="max-w-xl mx-auto">
            <p class="text-xs font-bold uppercase tracking-wider text-primary mb-2">FTSA Course</p>
            <h1 class="font-heading text-3xl font-extrabold text-primary mb-2">Daftar Course FTSA</h1>
            <p class="text-sm text-on-surface-variant mb-8">
                Masukkan kode perusahaan yang diberikan panitia. Akses FTSA terbuka selama 1 bulan dari tanggal workshop.
                Data &amp; hasil tetap tersimpan; setelah masa course berakhir bisa dibuka lagi saat kamu aktivasi YFD First Aid.
            </p>

            @if($result)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 mb-6">
                    <h2 class="font-bold text-emerald-900 text-lg mb-2">
                        {{ !empty($result['already_registered']) ? 'Kamu sudah terdaftar' : 'Pendaftaran berhasil' }}
                    </h2>
                    <p class="text-sm text-emerald-900/90 mb-4">
                        Course: <strong>{{ $result['course']->name }}</strong><br>
                        Perusahaan: <strong>{{ $result['course']->company_name }}</strong><br>
                        Akses sampai: <strong>{{ $result['access_ends_at'] }} WIB</strong>
                    </p>
                    <div class="rounded-xl bg-white border border-emerald-100 p-4 mb-4">
                        <div class="text-xs text-slate-500 mb-1">Login portal FTSA (email + kode lisensi)</div>
                        <div class="text-sm"><span class="text-slate-500">Email:</span> <strong>{{ $result['registrant']->email }}</strong></div>
                        <div class="text-sm mt-1"><span class="text-slate-500">Lisensi:</span> <code class="font-bold text-base">{{ $result['license_key'] ?: '—' }}</code></div>
                    </div>
                    <a href="{{ route('portal.login') }}" class="btn btn-gold btn-lg inline-flex">
                        Masuk Portal &amp; Isi FTSA
                    </a>
                    <p class="text-xs text-emerald-900/70 mt-3">
                        Simpan kode lisensi ini. Setelah 1 bulan akses course terkunci, tapi hasil FTSA tidak hilang.
                    </p>
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 mb-4">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(! $result)
                @if($course)
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 mb-4">
                        <strong>{{ $course->company_name }}</strong> — {{ $course->name }}<br>
                        Workshop: {{ $course->workshop_at?->format('d M Y') }} ·
                        @if($course->registrationWindowOpen())
                            Kode masih aktif sampai {{ $course->accessEndsAt()->timezone(config('portal.display_timezone', 'Asia/Jakarta'))->format('d M Y') }}
                        @else
                            <span class="text-rose-700 font-semibold">Kode sudah kedaluwarsa / nonaktif</span>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('ftsa-course.register.store') }}" class="rounded-2xl border border-outline-variant bg-white p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Kode daftar <span class="text-rose-600">*</span></label>
                        <input type="text" name="registration_code" value="{{ old('registration_code', $prefillCode) }}" required
                               class="w-full rounded-xl border border-slate-300 px-4 py-3 font-mono uppercase tracking-wide"
                               placeholder="FTSA-ACME-0926">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nama lengkap <span class="text-rose-600">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" required
                               class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Nama sesuai identitas">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Email <span class="text-rose-600">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="nama@email.com">
                        <p class="text-xs text-slate-500 mt-1">Pakai email yang sama nanti saat beli YFD First Aid agar hasil FTSA nyambung.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">WhatsApp</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="08xxxxxxxxxx">
                    </div>
                    <button type="submit" class="btn btn-gold btn-lg w-full justify-center">Daftar Course</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
