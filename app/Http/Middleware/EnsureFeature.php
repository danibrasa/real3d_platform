<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!$request->user()?->hasFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Feature not available in your plan.',
                    'upgrade_url' => route('admin.subscription.index'),
                ], 403);
            }
            return redirect()->route('admin.subscription.index')
                ->with('error', __('billing.feature_not_available'));
        }

        return $next($request);
    }
}
