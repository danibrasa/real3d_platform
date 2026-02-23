<x-portal-layout>
    <x-slot name="title">#{{ $tag->translated_name }} — {{ __('blog.blog') }}</x-slot>

    <section class="bg-gradient-to-b from-[#0a0a1e] to-gray-50 pt-12 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-block bg-gray-700 text-white text-xs font-medium px-3 py-1 rounded-full mb-4">Tag</span>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">#{{ $tag->translated_name }}</h1>
        </div>
    </section>

    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
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
                            <p class="text-gray-500">{{ __('blog.no_posts_tag') }}</p>
                        </div>
                    @endif
                </div>
                <div class="lg:col-span-1">
                    <x-portal-blog-sidebar :categories="$categories" :popularTags="$popularTags" />
                </div>
            </div>
        </div>
    </section>
</x-portal-layout>
