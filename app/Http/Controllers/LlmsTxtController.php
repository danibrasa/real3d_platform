<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Project;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class LlmsTxtController extends Controller
{
    public function show(): Response
    {
        $content = Cache::remember('llms_txt', 3600, function () {
            return $this->generateLlmsTxt();
        });

        return response($content, 200)
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }

    public function full(): Response
    {
        $content = Cache::remember('llms_full_txt', 3600, function () {
            return $this->generateLlmsFullTxt();
        });

        return response($content, 200)
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }

    private function generateLlmsTxt(): string
    {
        $lines = [];
        $lines[] = '# Real3D Properties';
        $lines[] = '';
        $lines[] = '> Real3D is a real estate platform specializing in 3D property visualization in the Dominican Republic and the Caribbean. We help investors find and explore properties with interactive 3D models and 360 video.';
        $lines[] = '';

        // Properties
        $projects = Project::portalVisible()
            ->with(['units' => fn ($q) => $q->where('status', 'available')])
            ->get();

        if ($projects->count()) {
            $lines[] = '## Available Properties';
            $lines[] = '';
            foreach ($projects as $project) {
                $available = $project->units->count();
                $minPrice = $project->units->min('price');
                $maxPrice = $project->units->max('price');
                $priceRange = $minPrice ? 'USD '.number_format($minPrice, 0).' - '.number_format($maxPrice, 0) : 'Contact for pricing';

                $lines[] = "- [{$project->name}](".url("/projects/{$project->slug}/info")."): {$project->location}. {$priceRange}. {$available} units available.";
            }
            $lines[] = '';
        }

        // Blog
        $posts = BlogPost::published()
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        if ($posts->count()) {
            $lines[] = '## Blog Articles';
            $lines[] = '';
            foreach ($posts as $post) {
                $excerpt = $post->excerpt ? " — {$post->excerpt}" : '';
                $lines[] = "- [{$post->title}](".route('blog.show', $post->slug)."){$excerpt}";
            }
            $lines[] = '';
        }

        // Categories
        $categories = BlogCategory::withCount(['posts' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->orderBy('sort_order')
            ->get();

        if ($categories->count()) {
            $lines[] = '## Blog Categories';
            $lines[] = '';
            foreach ($categories as $cat) {
                $lines[] = "- [{$cat->name}](".route('blog.category', $cat->slug).") ({$cat->posts_count} articles)";
            }
            $lines[] = '';
        }

        // Contact
        $lines[] = '## Contact';
        $lines[] = '';
        $lines[] = '- Website: '.url('/portal');
        $lines[] = '- Search Properties: '.route('portal.search');
        $lines[] = '- Blog: '.route('blog.index');
        $lines[] = '';
        $lines[] = '## Optional';
        $lines[] = '';
        $lines[] = '- [Full details (llms-full.txt)]('.url('/llms-full.txt').')';

        return implode("\n", $lines);
    }

    private function generateLlmsFullTxt(): string
    {
        $lines = [];
        $lines[] = '# Real3D Properties — Full Details';
        $lines[] = '';
        $lines[] = '> Comprehensive information about all properties and content on Real3D Properties, a real estate platform specializing in 3D property visualization in the Dominican Republic and the Caribbean.';
        $lines[] = '';

        // Properties with full details
        $projects = Project::portalVisible()
            ->with(['units' => fn ($q) => $q->where('status', 'available'), 'typologies'])
            ->get();

        if ($projects->count()) {
            $lines[] = '## Properties';
            $lines[] = '';

            foreach ($projects as $project) {
                $lines[] = "### {$project->name}";
                $lines[] = '';
                $lines[] = "- **Location:** {$project->location}";
                $lines[] = '- **URL:** '.url("/projects/{$project->slug}/info");

                if ($project->description) {
                    $lines[] = "- **Description:** {$project->description}";
                }

                $available = $project->units->count();
                $lines[] = "- **Available Units:** {$available}";

                $minPrice = $project->units->min('price');
                $maxPrice = $project->units->max('price');
                if ($minPrice) {
                    $lines[] = '- **Price Range:** USD '.number_format($minPrice, 0).' - '.number_format($maxPrice, 0);
                }

                if ($project->estimated_delivery) {
                    $lines[] = '- **Estimated Delivery:** '.$project->estimated_delivery->format('F Y');
                }

                // Investment metrics
                if ($project->rental_yield_annual) {
                    $lines[] = "- **Annual Rental Yield:** {$project->rental_yield_annual}%";
                }
                if ($project->average_occupancy) {
                    $lines[] = "- **Average Occupancy:** {$project->average_occupancy}%";
                }
                if ($project->appreciation_rate_annual) {
                    $lines[] = "- **Annual Appreciation:** {$project->appreciation_rate_annual}%";
                }

                // Unit types
                if ($project->typologies->count()) {
                    $lines[] = '';
                    $lines[] = '**Unit Types:**';
                    foreach ($project->typologies as $typ) {
                        $lines[] = "- {$typ->name}: {$typ->bedrooms} bed / {$typ->bathrooms} bath, {$typ->area_m2} m²";
                    }
                }

                $lines[] = '';
            }
        }

        // Blog with full excerpts
        $posts = BlogPost::published()
            ->with(['category', 'tags'])
            ->orderByDesc('published_at')
            ->get();

        if ($posts->count()) {
            $lines[] = '## Blog Articles';
            $lines[] = '';

            foreach ($posts as $post) {
                $lines[] = "### {$post->title}";
                $lines[] = '';
                $lines[] = '- **URL:** '.route('blog.show', $post->slug);
                if ($post->category) {
                    $lines[] = "- **Category:** {$post->category->name}";
                }
                $lines[] = '- **Published:** '.$post->published_at->format('Y-m-d');
                $lines[] = "- **Reading Time:** {$post->reading_time_minutes} min";

                if ($post->tags->count()) {
                    $lines[] = '- **Tags:** '.$post->tags->pluck('name')->implode(', ');
                }

                if ($post->excerpt) {
                    $lines[] = '';
                    $lines[] = $post->excerpt;
                }

                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }
}
