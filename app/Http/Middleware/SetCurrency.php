<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['USD', 'DOP', 'EUR', 'CAD'];
        $currency = $request->query('currency');

        if ($currency && in_array($currency, $supported)) {
            // Store in request attributes so CurrencyService can read it immediately
            $request->attributes->set('currency', $currency);
            $response = $next($request);

            return $response->withCookie(cookie('currency', $currency, 60 * 24 * 365));
        }

        return $next($request);
    }
}
