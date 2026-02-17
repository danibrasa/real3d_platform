<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiTokenController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-api-tokens');

        $tokens = ApiToken::with('tokenable')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.api-tokens.index', compact('tokens'));
    }

    public function create()
    {
        Gate::authorize('manage-api-tokens');

        $projects = Project::orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.api-tokens.create', compact('projects'));
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-api-tokens');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'rate_limit' => 'required|integer|in:60,120,300,600',
        ]);

        $user = $request->user();
        $projectIds = !empty($validated['project_ids']) ? array_map('intval', $validated['project_ids']) : null;

        $token = $user->createToken(
            $validated['name'],
            ['read'],
        );

        // Update the token with our custom fields
        $accessToken = $token->accessToken;
        $accessToken->description = $validated['description'] ?? null;
        $accessToken->project_ids = $projectIds;
        $accessToken->rate_limit = $validated['rate_limit'];
        $accessToken->save();

        return redirect()->route('admin.api-tokens.index')
            ->with('success', 'Token API creado.')
            ->with('plainTextToken', $token->plainTextToken);
    }

    public function edit(ApiToken $apiToken)
    {
        Gate::authorize('manage-api-tokens');

        $projects = Project::orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.api-tokens.edit', compact('apiToken', 'projects'));
    }

    public function update(Request $request, ApiToken $apiToken)
    {
        Gate::authorize('manage-api-tokens');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'rate_limit' => 'required|integer|in:60,120,300,600',
            'is_active' => 'boolean',
        ]);

        $apiToken->name = $validated['name'];
        $apiToken->description = $validated['description'] ?? null;
        $apiToken->project_ids = !empty($validated['project_ids']) ? array_map('intval', $validated['project_ids']) : null;
        $apiToken->rate_limit = $validated['rate_limit'];
        $apiToken->is_active = $request->boolean('is_active');
        $apiToken->save();

        return redirect()->route('admin.api-tokens.index')
            ->with('success', 'Token actualizado.');
    }

    public function destroy(ApiToken $apiToken)
    {
        Gate::authorize('manage-api-tokens');

        $apiToken->delete();

        return redirect()->route('admin.api-tokens.index')
            ->with('success', 'Token revocado.');
    }
}
