<?php

namespace App\Http\Controllers;

use App\Support\Salud\Comprobaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lo que la maquina dice de si misma, para que lo lea el vigilante de fuera.
 *
 * La comprobacion nocturna vive en la otra VM y no puede mirar dentro de esta:
 * no sabe si la cola avanza ni si el proveedor de correo contesta. Y con el
 * correo hay ademas una pescadilla -- si esta roto, el aviso no puede ir por
 * correo -- asi que el estado tiene que poder consultarse por la web.
 *
 * Detras de un token, y sin token la ruta no existe: esto cuenta como va la
 * maquina por dentro, y eso no se enseña por si acaso.
 */
class SaludController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(self::permitido($request), 404);

        $comprobaciones = Comprobaciones::todas();
        $bien = Comprobaciones::haySalud($comprobaciones);

        // 200 si todo va, 503 si no: asi un vigilante tonto -- un curl con
        // --fail, un monitor externo -- se entera sin leer el cuerpo.
        return response()->json([
            'ok' => $bien,
            'entorno' => app()->environment(),
            'comprobado' => now()->toIso8601String(),
            'comprobaciones' => $comprobaciones,
        ], $bien ? 200 : 503);
    }

    /**
     * Quien puede preguntar como va la maquina.
     *
     * Dos llaves, y con cualquiera basta:
     *
     *  - Un token en la configuracion, para consultarlo a mano.
     *  - La IP del vigilante, que vive en la otra VM. No es un secreto, asi que
     *    va en el repositorio y se despliega con el codigo: sin eso, encender
     *    esta comprobacion exigiria meter un secreto en el .env de produccion a
     *    mano, y una comprobacion que depende de que alguien se acuerde de un
     *    paso manual acaba apagada.
     *
     * Sin ninguna de las dos configurada la ruta no existe. Cerrada mientras no
     * se abra, y no al reves.
     */
    private static function permitido(Request $peticion): bool
    {
        $token = config('app.salud_token');

        if ($token && hash_equals($token, (string) $peticion->header('X-Salud', $peticion->query('clave', '')))) {
            return true;
        }

        return in_array($peticion->ip(), config('app.salud_ips', []), true);
    }
}
