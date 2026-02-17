<?php

namespace App\Http\Controllers;

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

        $content = view('sitemap', compact('projects'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
