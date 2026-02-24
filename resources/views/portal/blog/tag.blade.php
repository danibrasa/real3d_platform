<x-portal-layout>
    <x-slot name="title">#{{ $tag->translated_name }} — {{ __('blog.blog') }}</x-slot>

    <section class="bg-[#0a0a1e] pt-14 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-block bg-gray-700 text-white text-xs font-semibold px-4 py-1.5 rounded-full mb-5">Tag</span>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">#{{ $tag->translated_name }}</h1>
        </div>
    </section>

    <section class="-mt-10 pb-16 lg:pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2">
                    @if($posts->count())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                            @foreach($posts as $post)
                                <x-portal-blog-card :post="$post" />
                            @endforeach
                        </div>
                        <div class="mt-10">{{ $posts->links() }}</div>
                    @else
                        <div class="text-center py-20 bg-white rounded-2xl shadow-sm">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            <p class="text-gray-500 text-lg">{{ __('blog.no_posts_tag') }}</p>
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
