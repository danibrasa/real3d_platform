<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isInmobiliaria()) {
            return $next($request);
        }

        // If inmobiliaria has no company profile, redirect to onboarding
        if (!$user->companyProfile) {
            return redirect()->route('onboarding.company');
        }

        return $next($request);
    }
}
