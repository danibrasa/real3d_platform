@props(['categories' => collect(), 'popularTags' => collect(), 'featuredPosts' => collect()])

<aside class="space-y-8">
    {{-- Categories --}}
    @if($categories->count())
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-3">{{ __('blog.categories') }}</h3>
        <ul class="space-y-2">
            @foreach($categories as $cat)
                <li>
                    <a href="{{ route('blog.category', $cat->slug) }}"
                       class="flex items-center justify-between text-sm text-gray-600 hover:text-cyan-600 transition-colors py-1">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $cat->color }}"></span>
                            {{ $cat->translated_name }}
                        </span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $cat->posts_count }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Popular Tags --}}
    @if($popularTags->count())
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-3">{{ __('blog.popular_tags') }}</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($popularTags as $tag)
                <a href="{{ route('blog.tag', $tag->slug) }}"
                   class="text-xs bg-gray-100 text-gray-600 hover:bg-cyan-50 hover:text-cyan-700 px-3 py-1.5 rounded-full transition-colors">
                    {{ $tag->translated_name }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Featured Posts --}}
    @if($featuredPosts->count())
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-3">{{ __('blog.featured_posts') }}</h3>
        <ul class="space-y-3">
            @foreach($featuredPosts as $fp)
                <li>
                    <a href="{{ route('blog.show', $fp->slug) }}" class="flex gap-3 group">
                        @if($fp->featured_image_path)
                            <img src="{{ Storage::url($fp->featured_image_path) }}" alt="" class="w-16 h-12 rounded object-cover flex-shrink-0">
                        @endif
                        <div>
                            <span class="text-sm text-gray-800 group-hover:text-cyan-600 transition-colors line-clamp-2">{{ $fp->translated_title }}</span>
                            <span class="text-xs text-gray-400">{{ $fp->published_at?->format('d M') }}</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
</aside>
