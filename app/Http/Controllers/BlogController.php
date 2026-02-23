<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::published()
            ->with(['category', 'author', 'tags'])
            ->when($request->category, fn ($q, $slug) => $q->whereHas('category', fn ($cq) => $cq->where('slug', $slug)))
            ->when($request->tag, fn ($q, $slug) => $q->whereHas('tags', fn ($tq) => $tq->where('slug', $slug)))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = BlogCategory::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        $popularTags = BlogTag::withCount(['posts' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->limit(15)
            ->get();

        $featuredPosts = BlogPost::published()
            ->featured()
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('portal.blog.index', compact('posts', 'categories', 'popularTags', 'featuredPosts'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with(['category', 'author', 'tags'])
            ->firstOrFail();

        // Throttled view count (once per session per post)
        $viewedKey = 'blog_viewed_' . $post->id;
        if (!session()->has($viewedKey)) {
            $post->increment('views_count');
            session()->put($viewedKey, true);
        }

        $relatedPosts = $post->relatedPosts(3);

        return view('portal.blog.show', compact('post', 'relatedPosts'));
    }

    public function category(string $slug)
    {
        $category = BlogCategory::where('slug', $slug)->firstOrFail();

        $posts = BlogPost::published()
            ->where('category_id', $category->id)
            ->with(['category', 'author', 'tags'])
            ->orderByDesc('published_at')
            ->paginate(9);

        $categories = BlogCategory::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        $popularTags = BlogTag::withCount(['posts' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->limit(15)
            ->get();

        return view('portal.blog.category', compact('posts', 'category', 'categories', 'popularTags'));
    }

    public function tag(string $slug)
    {
        $tag = BlogTag::where('slug', $slug)->firstOrFail();

        $posts = BlogPost::published()
            ->whereHas('tags', fn ($q) => $q->where('slug', $slug))
            ->with(['category', 'author', 'tags'])
            ->orderByDesc('published_at')
            ->paginate(9);

        $categories = BlogCategory::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        $popularTags = BlogTag::withCount(['posts' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->limit(15)
            ->get();

        return view('portal.blog.tag', compact('posts', 'tag', 'categories', 'popularTags'));
    }
}
