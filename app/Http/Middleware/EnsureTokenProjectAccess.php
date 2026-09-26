<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenProjectAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token || ! $token->is_active) {
            return response()->json(['error' => 'Token is inactive or invalid.'], 401);
        }

        // Track IP
        if ($token->last_used_ip !== $request->ip()) {
            $token->forceFill(['last_used_ip' => $request->ip()])->saveQuietly();
        }

        // Check project access if route has a project parameter
        $project = $request->route('project');
        if ($project instanceof Project) {
            if (! $token->canAccessProject($project->id)) {
                return response()->json(['error' => 'Token does not have access to this project.'], 403);
            }
        }

        return $next($request);
    }
}
