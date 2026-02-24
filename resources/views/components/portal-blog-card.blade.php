@props(['post'])

<a href="{{ route('blog.show', $post->slug) }}"
   class="group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">

    {{-- Image --}}
    <div class="h-52 relative overflow-hidden bg-gray-100">
        @if($post->featured_image_path)
            <img src="{{ Storage::url($post->featured_image_path) }}"
                 alt="{{ $post->translated_featured_image_alt ?? $post->translated_title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                 loading="lazy">
        @else
            <div class="w-full h-full bg-gradient-to-br from-cyan-600/80 to-blue-700/80 flex items-center justify-center">
                <svg class="w-14 h-14 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                </svg>
            </div>
        @endif

        {{-- Category badge --}}
        @if($post->category)
            <span class="absolute top-3 left-3 text-white text-xs font-semibold px-3 py-1 rounded-full backdrop-blur-sm shadow-sm"
                  style="background: {{ $post->category->color }}dd">
                {{ $post->category->translated_name }}
            </span>
        @endif
    </div>

    {{-- Content --}}
    <div class="p-6">
        <h3 class="text-lg font-bold text-gray-900 group-hover:text-cyan-600 transition-colors line-clamp-2 mb-3 leading-snug">
            {{ $post->translated_title }}
        </h3>

        @if($post->translated_excerpt)
            <p class="text-sm text-gray-500 line-clamp-3 mb-4 leading-relaxed">{{ $post->translated_excerpt }}</p>
        @endif

        <div class="flex items-center justify-between text-xs text-gray-400 pt-4 border-t border-gray-100">
            <div class="flex items-center gap-2">
                @if($post->published_at)
                    <span>{{ $post->published_at->translatedFormat('d M, Y') }}</span>
                @endif
                <span>&middot;</span>
                <span>{{ $post->reading_time_minutes }} min</span>
            </div>
            @if($post->author)
                <span class="font-medium text-gray-500">{{ $post->author->name }}</span>
            @endif
        </div>
    </div>
</a>
