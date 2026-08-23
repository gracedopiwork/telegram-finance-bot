@extends('Companyprofile.layouts.main')

@section('title', 'Panduan First Aid — YFD')

@section('content')
@php $guide = $guide ?? config('portal_guide', []); @endphp

<section class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-16 md:py-24">
    <p class="text-xs font-bold uppercase tracking-wider text-primary mb-2">YFD First Aid</p>
    <h1 class="font-heading text-headline-lg text-primary mb-3">Panduan First Aid</h1>
    <p class="text-sm text-on-surface-variant mb-8 leading-relaxed">
        Source of truth web (Onboarding revisi 15 Agustus 2026). Buka bolak-balik kapan saja — daftar isi di bawah bisa diklik.
    </p>

    <nav class="rounded-2xl border border-outline-variant bg-white p-5 mb-10 space-y-2">
        <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-2">Daftar isi</p>
        @foreach(($guide['topics'] ?? []) as $topic)
            <a href="#{{ $topic['id'] }}" class="block text-sm text-primary font-semibold hover:underline">{{ $topic['title'] }}</a>
        @endforeach
        <a href="#faq" class="block text-sm text-primary font-semibold hover:underline">FAQ</a>
    </nav>

    <div class="space-y-8">
        @foreach(($guide['topics'] ?? []) as $topic)
            <article id="{{ $topic['id'] }}">
                <h2 class="font-heading text-lg text-primary mb-2">{{ $topic['title'] }}</h2>
                <p class="text-body-md text-on-surface-variant leading-relaxed whitespace-pre-line">{{ $topic['body'] }}</p>
            </article>
        @endforeach
    </div>

    <div id="faq" class="mt-12 space-y-4">
        <h2 class="font-heading text-lg text-primary mb-2">FAQ</h2>
        @foreach(($guide['faq'] ?? []) as $item)
            <details class="rounded-xl border border-outline-variant bg-white px-4 py-3">
                <summary class="text-sm font-semibold text-primary cursor-pointer">{{ $item['q'] }}</summary>
                <p class="text-sm text-on-surface-variant leading-relaxed mt-2">{{ $item['a'] }}</p>
            </details>
        @endforeach
    </div>

    <p class="mt-10 text-sm text-on-surface-variant">
        Butuh bantuan? WhatsApp Admin YFD <strong>{{ $guide['support_wa'] ?? '+62 851-1122-8911' }}</strong>.
    </p>
</section>
@endsection
