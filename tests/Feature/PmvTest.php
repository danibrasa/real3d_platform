<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Support\Pmv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Que lo que no es del PMV no se vea, y que el equipo lo siga viendo.
 *
 * Una promotora nueva se encontraba dieciseis entradas de menu, y entre
 * ellas blog, API, webhooks y un analisis estrategico en PDF. Nada de eso
 * lo ha probado nadie y todo se interpone entre ella y su primer visor. Se
 * esconde, no se borra, y la regla es una sola para el enlace y para la
 * pagina: sin eso acaba habiendo enlaces a un 404 o paginas vivas sin enlace.
 */
class PmvTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private User $equipo;

    private Project $proyecto;

    private Unit $vivienda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_ENTERPRISE,
            'max_projects' => 10,
        ]);

        $this->equipo = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($this->proyecto->id);

        // Con lo que el informe de inversion necesita para existir.
        $this->proyecto->update([
            'avg_nightly_rate' => 185,
            'average_occupancy' => 78,
            'management_fee' => 20,
            'property_tax_rate' => 1,
            'appreciation_rate_annual' => 12,
        ]);
        $this->vivienda = Unit::create([
            'project_id' => $this->proyecto->id,
            'identifier' => 'A-101',
            'floor' => 1,
            'sort_order' => 1,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_m2' => 85,
            'price' => 250000,
            'status' => 'available',
        ]);
    }

    /** Una direccion de cada zona escondida, para probar la puerta y no la lista. */
    private function unaDeCadaZona(): array
    {
        return [
            'blog' => route('admin.blog.posts.index'),
            'api' => route('admin.api-tokens.index'),
            'webhooks' => route('admin.webhooks.index'),
            'analisis' => route('admin.strategic-analysis.index'),
            'obra' => route('admin.projects.construction.index', $this->proyecto),
            'pagos' => route('admin.projects.payment-plans.index', $this->proyecto),
            'blog categorias' => route('admin.blog.categories.index'),
            'blog publico' => route('blog.index'),
            'monedas' => route('admin.currencies.index'),
            'mcp' => '/.well-known/mcp.json',
            'inversion' => route('viewer.investment.pdf', [$this->proyecto->slug, $this->vivienda->id]),
            'directorio' => route('directory.index'),
            'widget' => route('embed.show', $this->proyecto->slug),
        ];
    }

    public function test_hay_una_direccion_de_prueba_por_cada_zona_escondida(): void
    {
        // Lo dijo el revisor: faltaban dos zonas en la lista de arriba, y
        // una zona sin direccion de prueba es una zona que puede estar
        // reventando para el equipo sin que nada lo diga.
        $probadas = collect(array_keys($this->unaDeCadaZona()))
            ->map(fn ($clave) => explode(' ', $clave)[0])
            ->unique();

        foreach (array_keys(config('pmv.fuera')) as $zona) {
            $this->assertContains($zona, $probadas->all(), "la zona '{$zona}' no tiene direccion de prueba en unaDeCadaZona()");
        }
    }

    public function test_para_la_promotora_lo_escondido_no_existe(): void
    {
        foreach ($this->unaDeCadaZona() as $zona => $url) {
            $this->actingAs($this->promotora)->get($url)
                ->assertNotFound();
        }

        // Y sin sesion tampoco, ni lo que no tiene nombre de ruta.
        $this->get(route('blog.index'))->assertNotFound();
        $this->get('/.well-known/mcp.json')->assertNotFound();
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertNotFound();
        $this->getJson('/api/v1/projects')->assertNotFound();
    }

    public function test_el_equipo_lo_sigue_viendo_todo(): void
    {
        // No se exige 200 -- alguna pagina redirige o pide algo -- sino que
        // no sea el 404 del muro ni un error. "No es 404" a secas daba por
        // bueno el 500 que devolvia el blog del panel a todo el mundo.
        foreach ($this->unaDeCadaZona() as $zona => $url) {
            $codigo = $this->actingAs($this->equipo)->get($url)->getStatusCode();
            $this->assertNotSame(404, $codigo, "al equipo se le esconde la zona '{$zona}'");
            $this->assertLessThan(500, $codigo, "la zona '{$zona}' revienta para el equipo ({$codigo})");
        }

        $this->actingAs($this->equipo)->get(route('admin.blog.posts.index'))->assertOk();
    }

    public function test_el_menu_de_la_promotora_no_lleva_a_lo_escondido(): void
    {
        $panel = $this->actingAs($this->promotora)->get(route('admin.dashboard'))->assertOk();

        foreach (['admin.blog.posts.index', 'admin.api-tokens.index', 'admin.webhooks.index', 'admin.strategic-analysis.index'] as $ruta) {
            $panel->assertDontSee(route($ruta), false);
        }

        // Y lo que si es del PMV sigue en el menu.
        $panel->assertSee(route('admin.projects.index'), false);
        $panel->assertSee(route('admin.inquiries.index'), false);
    }

    public function test_el_menu_del_equipo_si(): void
    {
        $this->actingAs($this->equipo)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.blog.posts.index'), false)
            ->assertSee(route('admin.strategic-analysis.index'), false);
    }

    public function test_la_ficha_del_proyecto_no_ofrece_lo_escondido(): void
    {
        $this->actingAs($this->promotora)->get(route('admin.projects.edit', $this->proyecto))
            ->assertOk()
            ->assertDontSee(route('admin.projects.payment-plans.index', $this->proyecto), false)
            ->assertDontSee(route('admin.projects.construction.index', $this->proyecto), false);
    }

    public function test_el_interruptor_lo_devuelve_todo(): void
    {
        // Para la fase de salida, y para no tener que borrar nada mientras.
        config(['pmv.activo' => false]);

        $this->assertTrue(Pmv::activa('blog', $this->promotora));
        $this->actingAs($this->promotora)->get(route('admin.blog.posts.index'))->assertStatus(403);
        $this->get(route('blog.index'))->assertOk();
    }

    public function test_toda_zona_escondida_casa_con_alguna_ruta(): void
    {
        // Una zona cuyo patron no casa con ninguna ruta es una zona que se
        // cree escondida y no lo esta: el nombre cambio y nadie se entero.
        $rutas = collect(Route::getRoutes()->getRoutes());

        foreach (config('pmv.fuera') as $zona => $definicion) {
            $casa = $rutas->contains(fn ($ruta) => Pmv::zonaDe($ruta) === $zona);
            $this->assertTrue($casa, "la zona '{$zona}' no esconde ninguna ruta: revisa sus patrones");
        }
    }
}
