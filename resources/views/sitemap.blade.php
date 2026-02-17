{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Homepage --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

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
</urlset>
