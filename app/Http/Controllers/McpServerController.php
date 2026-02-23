<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class McpServerController extends Controller
{
    private const PROTOCOL_VERSION = '2024-11-05';

    public function discover(): JsonResponse
    {
        return response()->json([
            'name' => 'real3d-properties',
            'description' => 'Real3D Properties — Real estate platform with 3D visualization in the Dominican Republic and the Caribbean.',
            'url' => url('/mcp'),
            'protocol_version' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => true,
            ],
            'tools' => $this->toolDefinitions(),
        ]);
    }

    public function handle(Request $request): JsonResponse
    {
        $body = $request->all();
        $id = $body['id'] ?? null;
        $method = $body['method'] ?? '';

        return match ($method) {
            'initialize' => $this->rpcResponse($id, [
                'protocolVersion' => self::PROTOCOL_VERSION,
                'serverInfo' => [
                    'name' => 'real3d-properties',
                    'version' => '1.0.0',
                ],
                'capabilities' => [
                    'tools' => ['listChanged' => false],
                ],
            ]),

            'tools/list' => $this->rpcResponse($id, [
                'tools' => $this->toolDefinitions(),
            ]),

            'tools/call' => $this->handleToolCall($id, $body['params'] ?? []),

            'notifications/initialized' => response()->json(null, 204),

            default => $this->rpcError($id, -32601, 'Method not found'),
        };
    }

    private function handleToolCall($id, array $params): JsonResponse
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        return match ($name) {
            'list_properties' => $this->toolListProperties($id, $args),
            'get_property' => $this->toolGetProperty($id, $args),
            'list_blog_posts' => $this->toolListBlogPosts($id, $args),
            'search_content' => $this->toolSearchContent($id, $args),
            default => $this->rpcError($id, -32602, "Unknown tool: {$name}"),
        };
    }

    private function toolListProperties($id, array $args): JsonResponse
    {
        $query = Project::portalVisible()
            ->with(['units' => fn ($q) => $q->where('status', 'available')]);

        if (!empty($args['location'])) {
            $query->where('location', 'like', '%' . $args['location'] . '%');
        }

        if (!empty($args['min_price'])) {
            $query->whereHas('units', fn ($q) => $q->where('status', 'available')->where('price', '>=', $args['min_price']));
        }

        if (!empty($args['max_price'])) {
            $query->whereHas('units', fn ($q) => $q->where('status', 'available')->where('price', '<=', $args['max_price']));
        }

        if (!empty($args['bedrooms'])) {
            $query->whereHas('units', fn ($q) => $q->where('status', 'available')->where('bedrooms', '>=', $args['bedrooms']));
        }

        $projects = $query->limit(20)->get();

        $results = $projects->map(fn ($p) => [
            'name' => $p->name,
            'slug' => $p->slug,
            'location' => $p->location,
            'description' => $p->description,
            'available_units' => $p->units->count(),
            'price_range' => $p->price_range,
            'estimated_delivery' => $p->estimated_delivery?->format('Y-m'),
            'url' => url("/projects/{$p->slug}/info"),
            'rental_yield' => $p->rental_yield_annual,
            'occupancy' => $p->average_occupancy,
        ]);

        return $this->rpcResponse($id, [
            'content' => [
                ['type' => 'text', 'text' => json_encode(['properties' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
            ],
        ]);
    }

    private function toolGetProperty($id, array $args): JsonResponse
    {
        if (empty($args['slug'])) {
            return $this->rpcError($id, -32602, 'Missing required parameter: slug');
        }

        $project = Project::portalVisible()
            ->where('slug', $args['slug'])
            ->with(['units' => fn ($q) => $q->where('status', 'available'), 'typologies'])
            ->first();

        if (!$project) {
            return $this->rpcResponse($id, [
                'content' => [['type' => 'text', 'text' => 'Property not found.']],
            ]);
        }

        $data = [
            'name' => $project->name,
            'slug' => $project->slug,
            'location' => $project->location,
            'description' => $project->description,
            'url' => url("/projects/{$project->slug}/info"),
            'available_units' => $project->units->count(),
            'price_range' => $project->price_range,
            'estimated_delivery' => $project->estimated_delivery?->format('Y-m'),
            'rental_yield_annual' => $project->rental_yield_annual,
            'average_occupancy' => $project->average_occupancy,
            'appreciation_rate' => $project->appreciation_rate_annual,
            'unit_types' => $project->typologies->map(fn ($t) => [
                'name' => $t->name,
                'bedrooms' => $t->bedrooms,
                'bathrooms' => $t->bathrooms,
                'area_m2' => $t->area_m2,
            ]),
            'units' => $project->units->map(fn ($u) => [
                'name' => $u->name,
                'bedrooms' => $u->bedrooms,
                'bathrooms' => $u->bathrooms,
                'area_m2' => $u->area_m2,
                'price' => $u->price,
                'floor' => $u->floor,
            ]),
        ];

        return $this->rpcResponse($id, [
            'content' => [['type' => 'text', 'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]],
        ]);
    }

    private function toolListBlogPosts($id, array $args): JsonResponse
    {
        $query = BlogPost::published()->with(['category', 'tags']);

        if (!empty($args['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $args['category']));
        }

        if (!empty($args['search'])) {
            $search = $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $posts = $query->orderByDesc('published_at')->limit(20)->get();

        $results = $posts->map(fn ($p) => [
            'title' => $p->title,
            'slug' => $p->slug,
            'excerpt' => $p->excerpt,
            'category' => $p->category?->name,
            'published_at' => $p->published_at?->format('Y-m-d'),
            'reading_time' => $p->reading_time_minutes,
            'url' => route('blog.show', $p->slug),
            'tags' => $p->tags->pluck('name'),
        ]);

        return $this->rpcResponse($id, [
            'content' => [['type' => 'text', 'text' => json_encode(['posts' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]],
        ]);
    }

    private function toolSearchContent($id, array $args): JsonResponse
    {
        if (empty($args['query'])) {
            return $this->rpcError($id, -32602, 'Missing required parameter: query');
        }

        $search = $args['query'];

        $properties = Project::portalVisible()
            ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'type' => 'property',
                'name' => $p->name,
                'location' => $p->location,
                'url' => url("/projects/{$p->slug}/info"),
            ]);

        $posts = BlogPost::published()
            ->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%"))
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'type' => 'blog_post',
                'title' => $p->title,
                'excerpt' => $p->excerpt,
                'url' => route('blog.show', $p->slug),
            ]);

        $results = $properties->merge($posts);

        return $this->rpcResponse($id, [
            'content' => [['type' => 'text', 'text' => json_encode(['results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]],
        ]);
    }

    private function toolDefinitions(): array
    {
        return [
            [
                'name' => 'list_properties',
                'description' => 'List available real estate properties in the Dominican Republic. Can filter by location, price range, and number of bedrooms.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => ['type' => 'string', 'description' => 'Filter by location (e.g., "Punta Cana", "Cap Cana", "Bavaro")'],
                        'min_price' => ['type' => 'number', 'description' => 'Minimum price in USD'],
                        'max_price' => ['type' => 'number', 'description' => 'Maximum price in USD'],
                        'bedrooms' => ['type' => 'integer', 'description' => 'Minimum number of bedrooms'],
                    ],
                ],
            ],
            [
                'name' => 'get_property',
                'description' => 'Get detailed information about a specific property including units, pricing, investment metrics, and unit types.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'slug' => ['type' => 'string', 'description' => 'The property slug identifier'],
                    ],
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'list_blog_posts',
                'description' => 'List blog articles about real estate investment in the Dominican Republic. Can filter by category or search terms.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => ['type' => 'string', 'description' => 'Filter by category slug (e.g., "guia-inversion", "legal-fiscal", "mercado-inmobiliario")'],
                        'search' => ['type' => 'string', 'description' => 'Search term to filter posts'],
                    ],
                ],
            ],
            [
                'name' => 'search_content',
                'description' => 'Search across all properties and blog posts for relevant content about real estate in the Dominican Republic.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Search query'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    private function rpcResponse($id, array $result): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ]);
    }

    private function rpcError($id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ]);
    }
}
