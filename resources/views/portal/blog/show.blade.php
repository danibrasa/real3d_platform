<x-portal-layout>
    <x-slot name="title">{{ $post->translated_meta_title ?? $post->translated_title }}</x-slot>
    <x-slot name="metaDescription">{{ $post->translated_meta_description ?? $post->translated_excerpt }}</x-slot>

    @push('head')
    {{-- OG Tags --}}
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $post->translated_title }}">
    <meta property="og:description" content="{{ $post->translated_excerpt }}">
    <meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
    @if($post->featured_image_path)
        <meta property="og:image" content="{{ url(Storage::url($post->featured_image_path)) }}">
    @endif
    <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
    @if($post->category)
        <meta property="article:section" content="{{ $post->category->translated_name }}">
    @endif
    @if($post->meta_keywords)
        <meta name="keywords" content="{{ $post->meta_keywords }}">
    @endif

    {{-- Hreflang --}}
    <link rel="alternate" hreflang="es" href="{{ route('blog.show', ['slug' => $post->slug, 'lang' => 'es']) }}">
    <link rel="alternate" hreflang="en" href="{{ route('blog.show', ['slug' => $post->slug, 'lang' => 'en']) }}">

    {{-- JSON-LD Article --}}
    @php
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->translated_title,
        'description' => $post->translated_excerpt,
        'image' => $post->featured_image_path ? url(Storage::url($post->featured_image_path)) : null,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $post->author?->name ?? 'Real3D',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Real3D Properties',
            'url' => url('/'),
        ],
        'mainEntityOfPage' => route('blog.show', $post->slug),
        'wordCount' => str_word_count(strip_tags($post->translated_body ?? '')),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $jsonLd !!}</script>

    <style>
        .blog-body h2 { font-size: 1.625rem; font-weight: 700; color: #111827; margin-top: 2.5rem; margin-bottom: 1rem; line-height: 1.3; padding-bottom: 0.5rem; border-bottom: 2px solid #e5e7eb; }
        .blog-body h3 { font-size: 1.3rem; font-weight: 600; color: #1f2937; margin-top: 2rem; margin-bottom: 0.75rem; line-height: 1.4; }
        .blog-body p { color: #374151; line-height: 1.85; margin-bottom: 1.25rem; font-size: 1.0625rem; }
        .blog-body ul, .blog-body ol { margin-bottom: 1.25rem; padding-left: 1.5rem; }
        .blog-body li { color: #374151; line-height: 1.75; margin-bottom: 0.5rem; font-size: 1.0625rem; }
        .blog-body ul li { list-style-type: disc; }
        .blog-body ol li { list-style-type: decimal; }
        .blog-body strong { color: #111827; font-weight: 600; }
        .blog-body blockquote { border-left: 4px solid #06b6d4; background: #f0fdfa; padding: 1.25rem 1.5rem; margin: 1.5rem 0; border-radius: 0 0.75rem 0.75rem 0; }
        .blog-body blockquote p { color: #0e7490; font-style: italic; margin-bottom: 0; }
        .blog-body a { color: #0891b2; text-decoration: underline; text-underline-offset: 2px; }
        .blog-body a:hover { color: #0e7490; }
        .blog-body img { border-radius: 0.75rem; margin: 1.5rem 0; }
        .blog-body hr { border-color: #e5e7eb; margin: 2rem 0; }
        .blog-body table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: 0.9375rem; }
        .blog-body th { background: #f9fafb; font-weight: 600; text-align: left; padding: 0.75rem 1rem; border-bottom: 2px solid #e5e7eb; }
        .blog-body td { padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; }
    </style>
    @endpush

    {{-- Hero image --}}
    @if($post->featured_image_path)
        <div class="bg-[#0a0a1e]">
            <div class="max-w-5xl mx-auto">
                <div class="relative h-72 sm:h-96 md:h-[28rem] overflow-hidden rounded-b-3xl">
                    <img src="{{ Storage::url($post->featured_image_path) }}"
                         alt="{{ $post->translated_featured_image_alt ?? $post->translated_title }}"
                         class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20"></div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-[#0a0a1e] h-20"></div>
    @endif

    <article class="pb-16 lg:pb-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Article header --}}
            <header class="{{ $post->featured_image_path ? '-mt-24 relative z-10' : 'mt-8' }}">
                <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">
                    {{-- Breadcrumb --}}
                    <nav class="text-sm text-gray-400 mb-6 flex items-center gap-1.5 flex-wrap">
                        <a href="{{ route('portal.home') }}" class="hover:text-cyan-600 transition-colors">{{ __('blog.home') }}</a>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                        <a href="{{ route('blog.index') }}" class="hover:text-cyan-600 transition-colors">{{ __('blog.blog') }}</a>
                        @if($post->category)
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
                            <a href="{{ route('blog.category', $post->category->slug) }}" class="hover:text-cyan-600 transition-colors">{{ $post->category->translated_name }}</a>
                        @endif
                    </nav>

                    {{-- Category + Reading time --}}
                    <div class="flex items-center gap-3 mb-5">
                        @if($post->category)
                            <span class="text-white text-xs font-semibold px-3.5 py-1.5 rounded-full"
                                  style="background: {{ $post->category->color }}">
                                {{ $post->category->translated_name }}
                            </span>
                        @endif
                        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1.5 rounded-full">
                            {{ $post->reading_time_minutes }} min {{ __('blog.read') }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-3xl sm:text-4xl md:text-[2.75rem] font-extrabold text-gray-900 leading-tight mb-6 tracking-tight">
                        {{ $post->translated_title }}
                    </h1>

                    {{-- Excerpt --}}
                    @if($post->translated_excerpt)
                        <p class="text-lg text-gray-500 leading-relaxed mb-8 max-w-3xl">{{ $post->translated_excerpt }}</p>
                    @endif

                    {{-- Author + date bar --}}
                    <div class="flex flex-wrap items-center gap-6 pt-6 border-t border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ $post->author ? strtoupper(substr($post->author->name, 0, 1)) : 'R' }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $post->author?->name ?? 'Real3D' }}</p>
                                @if($post->published_at)
                                    <p class="text-xs text-gray-400">{{ $post->published_at->translatedFormat('d \d\e F, Y') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-4 ml-auto">
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ number_format($post->views_count) }}
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Article body --}}
            <div class="mt-10 grid grid-cols-1 lg:grid-cols-12 gap-10">
                {{-- Main content --}}
                <div class="lg:col-span-8">
                    <div class="blog-body">
                        {!! $post->bodyHtml() !!}
                    </div>

                    {{-- Tags --}}
                    @if($post->tags->count())
                        <div class="flex flex-wrap gap-2 mt-10 pt-8 border-t border-gray-200">
                            <span class="text-sm font-medium text-gray-500 mr-2 self-center">Tags:</span>
                            @foreach($post->tags as $tag)
                                <a href="{{ route('blog.tag', $tag->slug) }}"
                                   class="text-sm bg-gray-100 text-gray-600 hover:bg-cyan-50 hover:text-cyan-700 px-4 py-2 rounded-full transition-colors font-medium">
                                    #{{ $tag->translated_name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Share --}}
                    <div class="flex items-center gap-3 mt-8 py-6 px-6 bg-gray-50 rounded-xl">
                        <span class="text-sm font-semibold text-gray-700">{{ __('blog.share') }}:</span>
                        <div class="flex items-center gap-2 ml-auto">
                            <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->translated_title) }}&url={{ urlencode(route('blog.show', $post->slug)) }}"
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400 hover:text-black hover:shadow-md transition-all">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('blog.show', $post->slug)) }}"
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400 hover:text-blue-600 hover:shadow-md transition-all">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="https://wa.me/?text={{ urlencode($post->translated_title . ' ' . route('blog.show', $post->slug)) }}"
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400 hover:text-green-500 hover:shadow-md transition-all">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('blog.show', $post->slug)) }}"
                               target="_blank" rel="noopener"
                               class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-400 hover:text-blue-700 hover:shadow-md transition-all">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <aside class="lg:col-span-4">
                    <div class="sticky top-24 space-y-6">
                        {{-- Table of contents placeholder hint --}}
                        <div class="bg-white rounded-2xl shadow-sm p-6">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                {{ __('blog.about_article') }}
                            </h3>
                            <ul class="space-y-3 text-sm text-gray-500">
                                @if($post->category)
                                <li class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $post->category->color }}"></span>
                                    <a href="{{ route('blog.category', $post->category->slug) }}" class="hover:text-cyan-600 transition-colors">{{ $post->category->translated_name }}</a>
                                </li>
                                @endif
                                @if($post->published_at)
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                    {{ $post->published_at->translatedFormat('d \d\e F, Y') }}
                                </li>
                                @endif
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $post->reading_time_minutes }} min {{ __('blog.read') }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ number_format($post->views_count) }} {{ __('blog.views') }}
                                </li>
                            </ul>
                        </div>

                        {{-- CTA --}}
                        <div class="bg-gradient-to-br from-cyan-600 to-blue-700 rounded-2xl p-6 text-white">
                            <h3 class="font-bold text-lg mb-2">{{ __('blog.cta_title') }}</h3>
                            <p class="text-cyan-100 text-sm mb-4 leading-relaxed">{{ __('blog.cta_description') }}</p>
                            <a href="{{ route('portal.search') }}"
                               class="inline-block w-full text-center bg-white text-cyan-700 font-semibold text-sm px-5 py-3 rounded-xl hover:bg-cyan-50 transition-colors">
                                {{ __('blog.cta_button') }}
                            </a>
                        </div>
                    </div>
                </aside>
            </div>

            {{-- Related Posts --}}
            @if($relatedPosts->count())
                <section class="mt-16 pt-12 border-t border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8">{{ __('blog.related_posts') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($relatedPosts as $related)
                            <x-portal-blog-card :post="$related" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>
</x-portal-layout>
