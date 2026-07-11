@extends('Companyprofile.layouts.main')

@section('title', $article->title.' — Wealthpedia YFD')

@section('content')
<main class="max-w-container-max mx-auto px-margin-desktop py-12">
    <nav class="mb-8">
        <a href="{{ route('company.wealthpedia') }}"
           class="inline-flex items-center gap-1 font-label-md text-label-md text-primary-container hover:underline">
            ← Kembali ke Wealthpedia
        </a>
    </nav>

    <article class="max-w-3xl mx-auto">
        <header class="mb-8">
            @if($article->category)
                <span class="font-label-md text-label-md text-secondary tracking-wider uppercase block mb-3">{{ $article->category }}</span>
            @endif
            <h1 class="font-display-lg text-display-lg text-primary mb-4">{{ $article->title }}</h1>
            <div class="flex flex-wrap items-center gap-4 text-on-surface-variant font-caption text-caption">
                @if($article->read_time)
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">schedule</span>
                        {{ $article->read_time }}
                    </span>
                @endif
                @if($article->views_label)
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                        {{ $article->views_label }}
                    </span>
                @endif
            </div>
        </header>

        @if($article->image_url)
            <div class="aspect-video rounded-xl overflow-hidden mb-8 bg-primary-container/10">
                <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
            </div>
        @endif

        @if($article->description)
            <p class="font-body-lg text-body-lg text-on-surface-variant mb-8">{{ $article->description }}</p>
        @endif

        <div class="prose prose-lg max-w-none font-body-md text-body-md text-on-surface leading-relaxed space-y-4 [&_h2]:font-headline-md [&_h2]:text-primary [&_h2]:mt-8 [&_h2]:mb-3 [&_h3]:font-headline-sm [&_h3]:text-primary [&_h3]:mt-6 [&_h3]:mb-2 [&_p]:mb-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_a]:text-primary-container [&_a]:underline">
            @if(filled($article->content_html))
                {!! $article->content_html !!}
            @else
                <p class="text-on-surface-variant italic">Konten artikel belum tersedia.</p>
            @endif
        </div>
    </article>

    <div class="max-w-3xl mx-auto mt-12 pt-8 border-t border-outline-variant text-center">
        <a href="{{ route('company.wealthpedia') }}"
           class="inline-flex items-center justify-center bg-primary-container text-on-primary px-8 py-3 rounded-lg font-label-md text-label-md hover:opacity-90">
            Lihat artikel lain
        </a>
    </div>
</main>
@endsection
