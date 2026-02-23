<x-portal-layout>
    <x-slot name="title">{{ __('blog.blog') }}</x-slot>
    <x-slot name="metaDescription">{{ __('blog.meta_description') }}</x-slot>

    @push('head')
    <link rel="alternate" hreflang="es" href="{{ route('blog.index', ['lang' => 'es']) }}">
    <link rel="alternate" hreflang="en" href="{{ route('blog.index', ['lang' => 'en']) }}">
    @endpush

    {{-- Header --}}
    <section class="bg-gradient-to-b from-[#0a0a1e] to-gray-50 pt-12 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">{{ __('blog.blog_title') }}</h1>
            <p class="text-slate-400 max-w-2xl mx-auto">{{ __('blog.blog_subtitle') }}</p>
        </div>
    </section>

    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                {{-- Posts grid --}}
                <div class="lg:col-span-3">
                    @if($posts->count())
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($posts as $post)
                                <x-portal-blog-card :post="$post" />
                            @endforeach
                        </div>
                        <div class="mt-8">{{ $posts->links() }}</div>
                    @else
                        <div class="text-center py-16">
                            <p class="text-gray-500">{{ __('blog.no_posts') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <x-portal-blog-sidebar :categories="$categories" :popularTags="$popularTags" :featuredPosts="$featuredPosts" />
                </div>
            </div>
        </div>
    </section>
</x-portal-layout>
