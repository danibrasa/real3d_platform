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
    @endpush

    <article class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Breadcrumb --}}
            <nav class="text-sm text-gray-500 mb-6">
                <a href="{{ route('portal.home') }}" class="hover:text-cyan-600">{{ __('blog.home') }}</a>
                <span class="mx-1">/</span>
                <a href="{{ route('blog.index') }}" class="hover:text-cyan-600">{{ __('blog.blog') }}</a>
                @if($post->category)
                    <span class="mx-1">/</span>
                    <a href="{{ route('blog.category', $post->category->slug) }}" class="hover:text-cyan-600">{{ $post->category->translated_name }}</a>
                @endif
            </nav>

            {{-- Header --}}
            <header class="mb-8">
                @if($post->category)
                    <span class="inline-block text-white text-xs font-medium px-3 py-1 rounded-full mb-4"
                          style="background: {{ $post->category->color }}">
                        {{ $post->category->translated_name }}
                    </span>
                @endif

                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ $post->translated_title }}</h1>

                <div class="flex items-center gap-4 text-sm text-gray-500">
                    @if($post->author)
                        <span>{{ $post->author->name }}</span>
                    @endif
                    @if($post->published_at)
                        <span>{{ $post->published_at->format('d M, Y') }}</span>
                    @endif
                    <span>{{ $post->reading_time_minutes }} min {{ __('blog.read') }}</span>
                    <span>{{ number_format($post->views_count) }} {{ __('blog.views') }}</span>
                </div>
            </header>

            {{-- Featured Image --}}
            @if($post->featured_image_path)
                <div class="mb-8 rounded-xl overflow-hidden">
                    <img src="{{ Storage::url($post->featured_image_path) }}"
                         alt="{{ $post->translated_featured_image_alt ?? $post->translated_title }}"
                         class="w-full h-auto max-h-96 object-cover">
                </div>
            @endif

            {{-- Body --}}
            <div class="prose prose-lg max-w-none prose-headings:text-gray-900 prose-a:text-cyan-600 prose-img:rounded-xl mb-8">
                {!! $post->bodyHtml() !!}
            </div>

            {{-- Tags --}}
            @if($post->tags->count())
                <div class="flex flex-wrap gap-2 mb-8 pt-6 border-t border-gray-200">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.tag', $tag->slug) }}"
                           class="text-xs bg-gray-100 text-gray-600 hover:bg-cyan-50 hover:text-cyan-700 px-3 py-1.5 rounded-full transition-colors">
                            #{{ $tag->translated_name }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Share --}}
            <div class="flex items-center gap-4 mb-12 py-4 border-t border-b border-gray-200">
                <span class="text-sm font-medium text-gray-700">{{ __('blog.share') }}:</span>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->translated_title) }}&url={{ urlencode(route('blog.show', $post->slug)) }}"
                   target="_blank" rel="noopener" class="text-gray-400 hover:text-cyan-500 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('blog.show', $post->slug)) }}"
                   target="_blank" rel="noopener" class="text-gray-400 hover:text-blue-600 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://wa.me/?text={{ urlencode($post->translated_title . ' ' . route('blog.show', $post->slug)) }}"
                   target="_blank" rel="noopener" class="text-gray-400 hover:text-green-500 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
            </div>

            {{-- Related Posts --}}
            @if($relatedPosts->count())
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-6">{{ __('blog.related_posts') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($relatedPosts as $related)
                            <x-portal-blog-card :post="$related" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>
</x-portal-layout>
