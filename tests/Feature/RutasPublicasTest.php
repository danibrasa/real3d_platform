<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Que las rutas publicas de un proyecto enlacen todas igual.
 *
 * En /api/projects habia rutas por slug y rutas por id, vecinas y sin nada
 * que lo dijera. Costo dos tests que parecian pasar: pedian por slug a una
 * ruta que iba por id, recibian 404, y 404 era justo lo que esperaban -- el
 * muro que comprobaban podia no existir y no se habrian enterado. Se delato
 * quitando el muro a proposito.
 *
 * Este test no decide cual es la forma buena; decide que sea una sola.
 */
class RutasPublicasTest extends TestCase
{
    public function test_todas_las_rutas_publicas_de_un_proyecto_van_por_slug(): void
    {
        $porId = [];

        foreach (Route::getRoutes() as $ruta) {
            $uri = $ruta->uri();

            if (! str_starts_with($uri, 'api/projects/{project') && ! str_starts_with($uri, 'projects/{project')) {
                continue;
            }

            // El campo va aparte del uri: Route::uri() dice {project} tanto si
            // enlaza por id como por slug, y por eso se pregunta al binding.
            if ($ruta->bindingFieldFor('project') !== 'slug') {
                $porId[] = $uri;
            }
        }

        $this->assertSame([], $porId,
            "Estas rutas publicas enlazan el proyecto por id y sus vecinas por slug:\n  "
            .implode("\n  ", $porId)
            ."\nUna direccion que unas rutas entienden y otras no es un 404 que parece un muro.");
    }
}
