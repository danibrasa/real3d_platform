<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            Gate::authorize('manage-blog');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $posts = BlogPost::with(['category', 'author'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.blog.posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = BlogCategory::orderBy('sort_order')->get();

        return view('admin.blog.posts.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'excerpt' => 'nullable|string|max:500',
            'excerpt_en' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'body_en' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'featured_image_alt_en' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_title_en' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_description_en' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'tags' => 'nullable|string',
        ]);

        $data = collect($validated)->except(['featured_image', 'tags'])->toArray();
        $data['author_id'] = auth()->id();
        $data['is_featured'] = $request->boolean('is_featured');

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            $data['featured_image_path'] = $request->file('featured_image')
                ->store('blog', 'public');
        }

        $post = BlogPost::create($data);

        $this->syncTags($post, $request->input('tags', ''));

        return redirect()->route('admin.blog.posts.index')
            ->with('success', 'Articulo creado exitosamente.');
    }

    public function edit(BlogPost $post)
    {
        $categories = BlogCategory::orderBy('sort_order')->get();
        $post->load('tags');

        return view('admin.blog.posts.edit', compact('post', 'categories'));
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,'.$post->id,
            'excerpt' => 'nullable|string|max:500',
            'excerpt_en' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'body_en' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'featured_image_alt_en' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_title_en' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_description_en' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
            'is_featured' => 'boolean',
            'tags' => 'nullable|string',
        ]);

        $data = collect($validated)->except(['featured_image', 'tags'])->toArray();
        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['status'] === 'published' && ! $post->published_at && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            if ($post->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
            }
            $data['featured_image_path'] = $request->file('featured_image')
                ->store('blog', 'public');
        }

        $post->update($data);

        $this->syncTags($post, $request->input('tags', ''));

        return redirect()->route('admin.blog.posts.index')
            ->with('success', 'Articulo actualizado exitosamente.');
    }

    public function destroy(BlogPost $post)
    {
        if ($post->featured_image_path) {
            Storage::disk('public')->delete($post->featured_image_path);
        }
        $post->tags()->detach();
        $post->delete();

        return redirect()->route('admin.blog.posts.index')
            ->with('success', 'Articulo eliminado.');
    }

    private function syncTags(BlogPost $post, string $tagsString): void
    {
        $tagNames = array_filter(array_map('trim', explode(',', $tagsString)));
        $tagIds = [];

        foreach ($tagNames as $name) {
            $tag = BlogTag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $post->tags()->sync($tagIds);
    }
}
