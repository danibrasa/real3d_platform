<?php

namespace App\Http\Middleware;

use App\Support\Pmv;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las zonas que no son del PMV no existen para quien no es del equipo.
 *
 * 404 y no 403: una pagina que "no tienes permiso para ver" es una pagina
 * que existe, y lo que se quiere es que la promotora no sepa siquiera que
 * hay un blog o una API mientras no se los vayamos a dar. Va en los grupos
 * web y api, delante de todo lo demas.
 */
class FueraDelPmv
{
    public function handle(Request $request, Closure $next): Response
    {
        $ruta = $request->route();

        if ($ruta && Pmv::zonaDe($ruta) !== null && ! Pmv::activa(Pmv::zonaDe($ruta), $request->user() ?? $request->user('sanctum'))) {
            abort(404);
        }

        return $next($request);
    }
}
