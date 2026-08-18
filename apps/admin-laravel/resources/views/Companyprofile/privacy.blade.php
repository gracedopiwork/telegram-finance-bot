@extends('Companyprofile.layouts.main')

@section('title', 'Kebijakan Privasi — YFD First Aid')

@section('content')
@php $privacy = $privacy ?? config('portal_privacy', []); @endphp

<section class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-16 md:py-24">
    <p class="text-xs font-bold uppercase tracking-wider text-primary mb-2">YFD First Aid</p>
    <h1 class="font-heading text-headline-lg text-primary mb-3">{{ $privacy['title'] ?? 'Kebijakan privasi' }}</h1>
    <p class="text-sm text-on-surface-variant mb-8">
        Versi {{ $privacy['version'] ?? '' }} · {{ $privacy['updated_at'] ?? '' }}
        — teks ini sama dengan kebijakan di portal (Lapis 1 pra-pembelian, tanpa checkbox).
    </p>
    <p class="text-body-md text-on-surface mb-8 leading-relaxed">{{ $privacy['intro'] ?? '' }}</p>

    <div class="space-y-6">
        @foreach(($privacy['sections'] ?? []) as $section)
            <div>
                <h2 class="font-heading text-lg text-primary mb-2">{{ $section['heading'] }}</h2>
                <p class="text-body-md text-on-surface-variant leading-relaxed whitespace-pre-line">{{ $section['body'] }}</p>
            </div>
        @endforeach
    </div>

    <p class="mt-10 text-sm text-on-surface-variant">
        Permintaan hak subjek data: WhatsApp Admin YFD <strong>{{ $privacy['contact_wa'] ?? '+62 851-1122-8911' }}</strong>.
    </p>
</section>
@endsection
