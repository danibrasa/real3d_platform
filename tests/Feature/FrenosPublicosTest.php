<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Que ningun POST publico se pueda llamar sin fin.
 *
 * Son las puertas que no piden sesion ni CSRF: las llama cualquiera con un
 * guion. Cada una cuesta algo distinto -- correo del cupo, filas en la base,
 * llamadas al proveedor de IA -- y ninguna tenia freno salvo el chat. El
 * primer test es el que vale a largo plazo: recorre las rutas y exige que
 * toda ruta publica de escritura lleve un limitador, para que la siguiente
 * que se anada no se quede sin el como se quedaron estas.
 */
class FrenosPublicosTest extends TestCase
{
    use RefreshDatabase;

    public function test_todo_post_publico_lleva_freno(): void
    {
        $sinFreno = [];

        foreach (Route::getRoutes() as $ruta) {
            if (! in_array('POST', $ruta->methods(), true)) {
                continue;
            }

            $middleware = $ruta->gatherMiddleware();

            // Con sesion o con token ya hay a quien cortarle el paso.
            $conSesion = collect($middleware)->contains(fn ($m) => str_starts_with($m, 'auth'));
            $conFreno = collect($middleware)->contains(fn ($m) => str_starts_with($m, 'throttle:'));

            // El webhook de Stripe lo firma Stripe; frenarlo seria perder cobros.
            if ($conSesion || $conFreno || $ruta->uri() === 'stripe/webhook') {
                continue;
            }

            $sinFreno[] = $ruta->uri();
        }

        $this->assertSame([], $sinFreno,
            "Estos POST publicos se pueden llamar sin fin:\n  ".implode("\n  ", $sinFreno));
    }

    public function test_los_eventos_del_visor_tienen_tope_por_direccion(): void
    {
        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => User::factory()->create()->id,
        ]);

        $evento = [
            'project_id' => $proyecto->id,
            'session_id' => 'sesion-0001',
            'events' => [['type' => 'view', 'timestamp' => time()]],
        ];

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/viewer-events', $evento)->assertSuccessful();
        }

        $this->postJson('/api/viewer-events', $evento)->assertStatus(429);
    }

    public function test_el_servidor_mcp_tiene_tope_por_direccion(): void
    {
        $peticion = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/mcp', $peticion)->assertOk();
        }

        $this->postJson('/mcp', $peticion)->assertStatus(429);
    }
}
