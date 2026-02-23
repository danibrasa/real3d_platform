<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Project;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $projects = Project::where('status', 'published')
            ->select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $portalLocations = Project::portalVisible()
            ->whereNotNull('location')
            ->distinct()
            ->pluck('location');

        $blogPosts = BlogPost::published()
            ->select('slug', 'published_at', 'updated_at')
            ->orderByDesc('published_at')
            ->get();

        $blogCategories = BlogCategory::withCount(['posts' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->get();

        $content = view('sitemap', compact('projects', 'portalLocations', 'blogPosts', 'blogCategories'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
