<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStorageQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $profile = $this->resolveCompanyProfile($user);
        if ($profile && ! $profile->hasStorageAvailable()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => __('billing.storage_quota_exceeded'),
                    'upgrade_url' => route('admin.subscription.index'),
                ], 403);
            }

            return redirect()->route('admin.subscription.index')
                ->with('error', __('billing.storage_quota_exceeded'));
        }

        return $next($request);
    }

    private function resolveCompanyProfile(User $user)
    {
        if ($user->isInmobiliaria()) {
            return $user->companyProfile;
        }
        if ($user->isAgente() && $user->agency_id) {
            return User::find($user->agency_id)?->companyProfile;
        }

        return null;
    }
}
