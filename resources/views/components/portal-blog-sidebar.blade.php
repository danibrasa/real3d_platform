@props(['categories' => collect(), 'popularTags' => collect(), 'featuredPosts' => collect()])

<aside class="space-y-6">
    {{-- Categories --}}
    @if($categories->count())
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
            {{ __('blog.categories') }}
        </h3>
        <ul class="space-y-1">
            @foreach($categories as $cat)
                <li>
                    <a href="{{ route('blog.category', $cat->slug) }}"
                       class="flex items-center justify-between text-sm text-gray-600 hover:text-cyan-600 transition-colors py-2 px-3 rounded-lg hover:bg-gray-50">
                        <span class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $cat->color }}"></span>
                            {{ $cat->translated_name }}
                        </span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full font-medium">{{ $cat->posts_count }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Popular Tags --}}
    @if($popularTags->count())
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path d="M6 6h.008v.008H6V6z"/></svg>
            {{ __('blog.popular_tags') }}
        </h3>
        <div class="flex flex-wrap gap-2">
            @foreach($popularTags as $tag)
                <a href="{{ route('blog.tag', $tag->slug) }}"
                   class="text-sm bg-gray-100 text-gray-600 hover:bg-cyan-50 hover:text-cyan-700 px-3.5 py-2 rounded-full transition-colors font-medium">
                    {{ $tag->translated_name }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Featured Posts --}}
    @if($featuredPosts->count())
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
            {{ __('blog.featured_posts') }}
        </h3>
        <ul class="space-y-4">
            @foreach($featuredPosts as $fp)
                <li>
                    <a href="{{ route('blog.show', $fp->slug) }}" class="flex gap-3 group">
                        @if($fp->featured_image_path)
                            <img src="{{ Storage::url($fp->featured_image_path) }}" alt=""
                                 class="w-20 h-14 rounded-lg object-cover flex-shrink-0">
                        @else
                            <div class="w-20 h-14 rounded-lg bg-gradient-to-br from-cyan-500/20 to-blue-500/20 flex-shrink-0 flex items-center justify-center">
                                <svg class="w-5 h-5 text-cyan-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/></svg>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <span class="text-sm text-gray-800 group-hover:text-cyan-600 transition-colors line-clamp-2 font-medium leading-snug">{{ $fp->translated_title }}</span>
                            <span class="text-xs text-gray-400 mt-1 block">{{ $fp->published_at?->translatedFormat('d M') }}</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
</aside>
