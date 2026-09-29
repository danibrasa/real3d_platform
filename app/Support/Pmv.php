<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Routing\Route;

/**
 * Que zonas del producto se ensenan ahora mismo.
 *
 * El producto tiene mas de lo que el PMV necesita, y lo que sobra no se
 * borra: se esconde tras config/pmv.php hasta que alguien de verdad lo pida.
 * Aqui esta la unica regla, compartida por el middleware que responde 404 y
 * por las vistas que dejan de pintar el enlace: una zona escondida para
 * quien no es del equipo. Dos reglas separadas son como se acaba con un
 * enlace a una pagina que da 404, o con una pagina viva sin enlace.
 */
class Pmv
{
    /** Si esa zona se ensena a quien mira ahora mismo. */
    public static function activa(string $zona, ?User $usuario = null): bool
    {
        if (! config('pmv.activo')) {
            return true;
        }

        if (! isset(config('pmv.fuera')[$zona])) {
            return true;
        }

        return self::esDelEquipo($usuario ?? auth()->user());
    }

    /** La zona escondida a la que pertenece una ruta, o null si se ve. */
    public static function zonaDe(Route $ruta): ?string
    {
        $nombre = $ruta->getName() ?? '';
        $uri = $ruta->uri();

        foreach (config('pmv.fuera', []) as $zona => $definicion) {
            foreach ($definicion['rutas'] ?? [] as $patron) {
                if ($nombre !== '' && fnmatch($patron, $nombre)) {
                    return $zona;
                }
            }
            foreach ($definicion['uris'] ?? [] as $patron) {
                if (fnmatch($patron, $uri)) {
                    return $zona;
                }
            }
        }

        return null;
    }

    public static function esDelEquipo(?User $usuario): bool
    {
        return $usuario !== null && $usuario->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
    }
}
