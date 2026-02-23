{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Homepage --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Portal home --}}
    <url>
        <loc>{{ route('portal.home') }}</loc>
        <lastmod>{{ $projects->first()?->updated_at?->toW3cString() ?? now()->toW3cString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    {{-- Portal search --}}
    <url>
        <loc>{{ route('portal.search') }}</loc>
        <lastmod>{{ $projects->first()?->updated_at?->toW3cString() ?? now()->toW3cString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    {{-- Portal search by location --}}
    @foreach($portalLocations as $location)
    <url>
        <loc>{{ route('portal.search', ['location' => $location]) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    {{-- Projects listing --}}
    <url>
        <loc>{{ url('/projects') }}</loc>
        <lastmod>{{ $projects->first()?->updated_at?->toW3cString() ?? now()->toW3cString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    {{-- Individual project pages --}}
    @foreach($projects as $project)
    <url>
        <loc>{{ url('/projects/' . $project->slug . '/info') }}</loc>
        <lastmod>{{ $project->updated_at->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ url('/projects/' . $project->slug) }}</loc>
        <lastmod>{{ $project->updated_at->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    {{-- Blog index --}}
    <url>
        <loc>{{ route('blog.index') }}</loc>
        <lastmod>{{ $blogPosts->first()?->published_at?->toW3cString() ?? now()->toW3cString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    {{-- Blog categories --}}
    @foreach($blogCategories as $cat)
    <url>
        <loc>{{ route('blog.category', $cat->slug) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach

    {{-- Blog posts --}}
    @foreach($blogPosts as $blogPost)
    <url>
        <loc>{{ route('blog.show', $blogPost->slug) }}</loc>
        <lastmod>{{ ($blogPost->updated_at ?? $blogPost->published_at)->toW3cString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
</urlset>
