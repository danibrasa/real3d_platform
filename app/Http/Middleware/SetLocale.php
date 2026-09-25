<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED = ['es', 'en'];

    private const DEFAULT = 'es';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        app()->setLocale($locale);

        // Store in cookie if changed via query param
        $response = $next($request);

        if ($request->has('lang') && in_array($request->get('lang'), self::SUPPORTED)) {
            $response->cookie('locale', $locale, 60 * 24 * 365); // 1 year
        }

        return $response;
    }

    private function detectLocale(Request $request): string
    {
        // 1. Query parameter ?lang=en
        if ($request->has('lang') && in_array($request->get('lang'), self::SUPPORTED)) {
            return $request->get('lang');
        }

        // 2. Cookie
        $cookie = $request->cookie('locale');
        if ($cookie && in_array($cookie, self::SUPPORTED)) {
            return $cookie;
        }

        // 3. Accept-Language header
        $header = $request->header('Accept-Language', '');
        foreach (self::SUPPORTED as $lang) {
            if (str_starts_with($header, $lang)) {
                return $lang;
            }
        }

        return self::DEFAULT;
    }
}
