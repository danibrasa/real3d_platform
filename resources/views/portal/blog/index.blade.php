<x-portal-layout>
    <x-slot name="title">{{ __('blog.blog') }}</x-slot>
    <x-slot name="metaDescription">{{ __('blog.meta_description') }}</x-slot>

    @push('head')
    <link rel="alternate" hreflang="es" href="{{ route('blog.index', ['lang' => 'es']) }}">
    <link rel="alternate" hreflang="en" href="{{ route('blog.index', ['lang' => 'en']) }}">
    @endpush

    {{-- Header --}}
    <section class="bg-[#0a0a1e] pt-14 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">{{ __('blog.blog_title') }}</h1>
            <p class="text-slate-400 max-w-2xl mx-auto text-lg">{{ __('blog.blog_subtitle') }}</p>
        </div>
    </section>

    <section class="-mt-10 pb-16 lg:pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($posts->count())
                {{-- Featured / first post — hero card --}}
                @php $featured = $posts->first(); @endphp
                <a href="{{ route('blog.show', $featured->slug) }}"
                   class="group block bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 mb-10">
                    <div class="grid md:grid-cols-2">
                        {{-- Image --}}
                        <div class="h-64 md:h-80 relative overflow-hidden bg-gray-100">
                            @if($featured->featured_image_path)
                                <img src="{{ Storage::url($featured->featured_image_path) }}"
                                     alt="{{ $featured->translated_featured_image_alt ?? $featured->translated_title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-cyan-600 to-blue-700 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/></svg>
                                </div>
                            @endif
                        </div>
                        {{-- Content --}}
                        <div class="p-8 md:p-10 flex flex-col justify-center">
                            @if($featured->category)
                                <span class="inline-block self-start text-white text-xs font-semibold px-3 py-1 rounded-full mb-4"
                                      style="background: {{ $featured->category->color }}">
                                    {{ $featured->category->translated_name }}
                                </span>
                            @endif
                            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 group-hover:text-cyan-600 transition-colors mb-4 leading-tight">
                                {{ $featured->translated_title }}
                            </h2>
                            <p class="text-gray-500 mb-6 line-clamp-3 leading-relaxed">{{ $featured->translated_excerpt }}</p>
                            <div class="flex items-center gap-4 text-sm text-gray-400">
                                @if($featured->published_at)
                                    <span>{{ $featured->published_at->translatedFormat('d M, Y') }}</span>
                                @endif
                                <span>&middot;</span>
                                <span>{{ $featured->reading_time_minutes }} min {{ __('blog.read') }}</span>
                                @if($featured->author)
                                    <span>&middot;</span>
                                    <span>{{ $featured->author->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>

                {{-- Main grid: posts + sidebar --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    {{-- Posts --}}
                    <div class="lg:col-span-2">
                        @if($posts->count() > 1)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                @foreach($posts->slice(1) as $post)
                                    <x-portal-blog-card :post="$post" />
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-10">{{ $posts->links() }}</div>
                    </div>

                    {{-- Sidebar --}}
                    <div class="lg:col-span-1">
                        <x-portal-blog-sidebar :categories="$categories" :popularTags="$popularTags" :featuredPosts="$featuredPosts" />
                    </div>
                </div>
            @else
                <div class="text-center py-20">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <p class="text-gray-500 text-lg">{{ __('blog.no_posts') }}</p>
                </div>
            @endif
        </div>
    </section>
</x-portal-layout>
