@props(['post'])

<a href="{{ route('blog.show', $post->slug) }}"
   class="group block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">

    {{-- Image --}}
    <div class="h-48 relative overflow-hidden bg-gray-100">
        @if($post->featured_image_path)
            <img src="{{ Storage::url($post->featured_image_path) }}"
                 alt="{{ $post->translated_featured_image_alt ?? $post->translated_title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy">
        @else
            <div class="w-full h-full bg-gradient-to-br from-cyan-500/80 to-blue-600/80 flex items-center justify-center">
                <svg class="w-12 h-12 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                </svg>
            </div>
        @endif

        {{-- Category badge --}}
        @if($post->category)
            <span class="absolute top-3 left-3 text-white text-xs font-medium px-2.5 py-0.5 rounded-md backdrop-blur-sm"
                  style="background: {{ $post->category->color }}cc">
                {{ $post->category->translated_name }}
            </span>
        @endif
    </div>

    {{-- Content --}}
    <div class="p-5">
        <h3 class="font-semibold text-gray-900 group-hover:text-cyan-600 transition-colors line-clamp-2 mb-2">
            {{ $post->translated_title }}
        </h3>

        @if($post->translated_excerpt)
            <p class="text-sm text-gray-500 line-clamp-2 mb-3">{{ $post->translated_excerpt }}</p>
        @endif

        <div class="flex items-center justify-between text-xs text-gray-400">
            <div class="flex items-center gap-3">
                @if($post->published_at)
                    <span>{{ $post->published_at->format('d M, Y') }}</span>
                @endif
                <span>{{ $post->reading_time_minutes }} min {{ __('blog.read') }}</span>
            </div>
            @if($post->author)
                <span>{{ $post->author->name }}</span>
            @endif
        </div>
    </div>
</a>
