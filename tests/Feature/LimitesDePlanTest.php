<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Que cada limite del plan tenga de verdad una puerta.
 *
 * Nace de una auditoria que salio mal. El visor 3D era lo unico que separaba el
 * plan gratuito del de pago y no lo comprobaba nadie; al mirar los demas
 * limites, tres mas estaban igual. Un limite escrito en una tabla y no
 * comprobado en ningun sitio no es un limite: es una intencion.
 *
 * El primer test es el que importa a largo plazo. Recorre PLAN_LIMITS y exige
 * que cada clave declare aqui el test que la cubre, y que ese test exista. No
 * demuestra que la puerta funcione -eso lo hace cada test por su cuenta- pero
 * hace imposible anadir un limite nuevo sin decidir quien lo comprueba, que es
 * exactamente lo que fallo.
 */
class LimitesDePlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cada limite del plan y el test que lo cubre.
     *
     * Al anadir un limite a PLAN_LIMITS hay que anadirlo aqui, y escribir el
     * test. Si no, el primer test de esta clase falla y dice cual falta.
     */
    private const PUERTA = [
        'max_projects' => 'test_el_plan_gratuito_solo_permite_un_proyecto',
        'max_storage_bytes' => 'test_pasado_el_almacenamiento_no_se_sube_mas',
        'max_units_per_project' => 'test_no_se_pasan_las_viviendas_del_plan_ni_una_a_una',
        'max_agents' => 'test_el_plan_gratuito_no_permite_agentes',
        'chatbot' => 'test_el_chatbot_es_de_los_planes_de_pago',
        'analytics' => 'test_las_analiticas_son_de_los_planes_de_pago',
        'api_access' => 'test_la_api_es_de_enterprise',
        'embed_widget' => 'test_el_widget_incrustable_es_de_los_planes_de_pago',
        'visor_3d' => 'test_el_visor_es_de_los_planes_de_pago',
    ];

    public function test_todo_limite_declara_quien_lo_comprueba(): void
    {
        // La union de los tres planes, no solo el gratuito: una clave anadida
        // unicamente a Professional se habria colado por debajo de esta red,
        // que es justo lo que la red existe para impedir.
        $todas = [];
        foreach (CompanyProfile::PLAN_LIMITS as $limites) {
            $todas = array_merge($todas, array_keys($limites));
        }

        foreach (array_unique($todas) as $limite) {
            $this->assertArrayHasKey($limite, self::PUERTA,
                "El limite '{$limite}' no declara que test lo comprueba. "
                .'Un limite sin puerta no es un limite: es una intencion.');

            $this->assertTrue(method_exists($this, self::PUERTA[$limite]),
                "El limite '{$limite}' declara el test '".self::PUERTA[$limite]."', que no existe.");
        }
    }

    // --- Ayudas -----------------------------------------------------------

    private function promotora(string $plan): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        $limites = CompanyProfile::PLAN_LIMITS[$plan];

        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia-'.$user->id,
            'plan_tier' => $plan,
            'max_projects' => $limites['max_projects'],
            'max_storage_bytes' => $limites['max_storage_bytes'],
        ]);

        return $user->fresh();
    }

    private function proyectoDe(User $user, string $sufijo = ''): Project
    {
        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia-'.$user->id.$sufijo,
            'status' => 'public',
            'created_by' => $user->id,
        ]);

        $user->assignedProjects()->attach($proyecto->id);

        return $proyecto;
    }

    // --- Una puerta por limite --------------------------------------------

    public function test_el_plan_gratuito_solo_permite_un_proyecto(): void
    {
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);
        $this->proyectoDe($promotora);

        $this->actingAs($promotora)->post(route('admin.projects.store'), [
            'name' => 'Un segundo proyecto',
        ]);

        $this->assertSame(1, $promotora->assignedProjects()->count(),
            'el plan de un proyecto permitio crear el segundo');
    }

    public function test_pasado_el_almacenamiento_no_se_sube_mas(): void
    {
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);
        $proyecto = $this->proyectoDe($promotora);

        $perfil = $promotora->companyProfile;
        $perfil->update(['storage_used_bytes' => $perfil->max_storage_bytes]);

        $this->actingAs($promotora)
            ->postJson(route('admin.projects.upload.init', $proyecto), [
                'file_type' => 'model_3d',
                'file_name' => 'modelo.glb',
                'file_size' => 1024,
                'total_chunks' => 1,
            ])
            ->assertForbidden();
    }

    public function test_no_se_pasan_las_viviendas_del_plan_ni_una_a_una(): void
    {
        // La importacion si lo comprobaba. Creandolas a mano, no: el tope del
        // plan se saltaba con paciencia.
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);
        $proyecto = $this->proyectoDe($promotora);
        $tope = CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER]['max_units_per_project'];

        for ($i = 1; $i <= $tope; $i++) {
            Unit::create([
                'project_id' => $proyecto->id,
                'identifier' => 'A-'.$i,
                'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
                'price' => 185000, 'status' => 'available', 'sort_order' => $i,
            ]);
        }

        $this->actingAs($promotora)->post(route('admin.projects.units.store', $proyecto), [
            'identifier' => 'LA-QUE-SOBRA',
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
            'price' => 185000, 'status' => 'available',
        ]);

        $this->assertSame($tope, $proyecto->units()->count(),
            "el plan permitio pasar de {$tope} viviendas creandolas a mano");
    }

    public function test_el_equipo_tampoco_le_pasa_el_tope_a_una_promotora(): void
    {
        // El tope salia del perfil de quien hacia la peticion. El equipo no
        // tiene ficha de empresa, asi que anadiendo viviendas en nombre de una
        // promotora del plan gratuito no se le aplicaba el suyo: el mismo
        // limite, saltado por otra puerta.
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);
        $proyecto = $this->proyectoDe($promotora, '-equipo');
        $tope = CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER]['max_units_per_project'];

        for ($i = 1; $i <= $tope; $i++) {
            Unit::create([
                'project_id' => $proyecto->id,
                'identifier' => 'B-'.$i,
                'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
                'price' => 185000, 'status' => 'available', 'sort_order' => $i,
            ]);
        }

        $equipo = User::factory()->create(['role' => User::ROLE_GESTOR]);

        $this->actingAs($equipo)->post(route('admin.projects.units.store', $proyecto), [
            'identifier' => 'LA-DEL-EQUIPO',
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
            'price' => 185000, 'status' => 'available',
        ]);

        $this->assertSame($tope, $proyecto->units()->count(),
            'el equipo paso el tope del plan de la promotora');
    }

    public function test_el_plan_gratuito_no_permite_agentes(): void
    {
        // Starter trae max_agents = 0, y no lo miraba nadie.
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);

        $this->actingAs($promotora)->post(route('admin.users.store'), [
            'name' => 'Agente Nuevo',
            'email' => 'agente.nuevo@ejemplo.invalid',
            'password' => 'UnaClaveLarga2026!',
            'password_confirmation' => 'UnaClaveLarga2026!',
            'role' => User::ROLE_AGENTE,
        ]);

        $this->assertSame(0, User::where('agency_id', $promotora->id)->count(),
            'el plan sin agentes permitio crear uno');
    }

    public function test_el_equipo_tampoco_le_pasa_el_tope_de_agentes(): void
    {
        // La puerta de al lado de la anterior, y la deje igual de abierta: el
        // equipo puede crear un agente indicando la agencia, y ahi el tope no
        // se miraba. Arreglar un camino y dejar el gemelo es peor que no
        // arreglar ninguno, porque parece hecho.
        $promotora = $this->promotora(CompanyProfile::PLAN_STARTER);
        $equipo = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $this->actingAs($equipo)->post(route('admin.users.store'), [
            'name' => 'Agente Del Equipo',
            'email' => 'agente.equipo@ejemplo.invalid',
            'password' => 'UnaClaveLarga2026!',
            'password_confirmation' => 'UnaClaveLarga2026!',
            'role' => User::ROLE_AGENTE,
            'agency_id' => $promotora->id,
        ]);

        $this->assertSame(0, User::where('agency_id', $promotora->id)->count(),
            'el equipo le creo un agente a una promotora que no puede tenerlos');
    }

    public function test_un_plan_de_pago_si_pasa_del_tope_gratuito(): void
    {
        // Solo estaba probado que el gratuito se bloquea. Un tope calculado mal
        // -- devolviendo cero para todos -- habria pasado ese test tan tranquilo
        // y habria dejado a los clientes de pago sin poder anadir nada.
        $promotora = $this->promotora(CompanyProfile::PLAN_PROFESSIONAL);
        $proyecto = $this->proyectoDe($promotora, '-pro-tope');
        $gratuito = CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER]['max_units_per_project'];

        for ($i = 1; $i <= $gratuito; $i++) {
            Unit::create([
                'project_id' => $proyecto->id,
                'identifier' => 'C-'.$i,
                'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
                'price' => 185000, 'status' => 'available', 'sort_order' => $i,
            ]);
        }

        $this->actingAs($promotora)->post(route('admin.projects.units.store', $proyecto), [
            'identifier' => 'LA-QUE-SI-CABE',
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
            'price' => 185000, 'status' => 'available',
        ]);

        $this->assertSame($gratuito + 1, $proyecto->units()->count(),
            'el plan de pago se quedo con el tope del gratuito');
    }

    public function test_un_plan_de_pago_si_permite_agentes(): void
    {
        $promotora = $this->promotora(CompanyProfile::PLAN_PROFESSIONAL);

        $this->actingAs($promotora)->post(route('admin.users.store'), [
            'name' => 'Agente Legitimo',
            'email' => 'agente.legitimo@ejemplo.invalid',
            'password' => 'UnaClaveLarga2026!',
            'password_confirmation' => 'UnaClaveLarga2026!',
            'role' => User::ROLE_AGENTE,
        ]);

        $this->assertSame(1, User::where('agency_id', $promotora->id)->count(),
            'el plan de pago no dejo crear ni un agente');
    }

    public function test_el_chatbot_es_de_los_planes_de_pago(): void
    {
        // El gate del panel estaba, y este test lo miraba y daba el limite por
        // cubierto. La puerta publica no estaba: el widget se pintaba y la API
        // contestaba para cualquier proyecto publicado, y cada respuesta la
        // paga esta casa al proveedor de IA. El visor otra vez, con factura.
        // Por eso aqui se prueban las dos puertas publicas y no el gate.
        config(['chatbot.enabled' => true, 'chatbot.provider' => 'anthropic']);
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['text' => 'Claro, dime.']]]),
        ]);

        $gratuita = $this->promotora(CompanyProfile::PLAN_STARTER);
        $suyo = $this->proyectoDe($gratuita, '-chat');

        $this->assertFalse(Gate::forUser($gratuita)->allows('use-chatbot'));
        $this->get(route('viewer.landing', $suyo))->assertOk()->assertDontSee('chatbotWidget()', false);
        $this->postJson('/api/projects/'.$suyo->slug.'/chat', ['message' => 'Hola', 'session_id' => 'sesion-gratis-0001'])
            ->assertStatus(503);
        Http::assertNothingSent();

        $pagando = $this->promotora(CompanyProfile::PLAN_PROFESSIONAL);
        $deEsa = $this->proyectoDe($pagando, '-chat-pro');

        $this->assertTrue(Gate::forUser($pagando)->allows('use-chatbot'));
        $this->get(route('viewer.landing', $deEsa))->assertOk()->assertSee('chatbotWidget()', false);
        $this->postJson('/api/projects/'.$deEsa->slug.'/chat', ['message' => 'Hola', 'session_id' => 'sesion-pago-0001'])
            ->assertOk()
            ->assertJsonPath('message', 'Claro, dime.');
    }

    public function test_las_analiticas_son_de_los_planes_de_pago(): void
    {
        $this->assertFalse(Gate::forUser($this->promotora(CompanyProfile::PLAN_STARTER))->allows('use-analytics'));
        $this->assertTrue(Gate::forUser($this->promotora(CompanyProfile::PLAN_PROFESSIONAL))->allows('use-analytics'));
    }

    public function test_la_api_es_de_enterprise(): void
    {
        // La puerta de verdad esta en crear el token: sin token, /api/v1 no
        // contesta a nadie.
        $this->assertFalse(Gate::forUser($this->promotora(CompanyProfile::PLAN_PROFESSIONAL))->allows('manage-api-tokens'));
        $this->assertTrue(Gate::forUser($this->promotora(CompanyProfile::PLAN_ENTERPRISE))->allows('manage-api-tokens'));
    }

    public function test_el_visor_es_de_los_planes_de_pago(): void
    {
        // Era una regla aparte -"plan distinto de starter"- y por eso quedaba
        // fuera de esta auditoria. Ahora es un limite mas de la tabla, que es
        // como tendria que haber estado: fue el unico que se comprobaba a mano
        // y el unico que estuvo meses sin puerta.
        $gratuita = $this->promotora(CompanyProfile::PLAN_STARTER);
        $suyo = $this->proyectoDe($gratuita, '-visor');

        // Por la ruta publica y no por el ayudante, como los demas de esta
        // clase. Comprobar solo el ayudante no demuestra que la ruta lo llame:
        // un gate perfecto al que nadie invoca es codigo muerto, y de eso ya
        // hay un ejemplo en esta misma tabla.
        $this->get(route('viewer.show', $suyo))->assertRedirect(route('viewer.landing', $suyo));

        $pagando = $this->promotora(CompanyProfile::PLAN_PROFESSIONAL);
        $deEsa = $this->proyectoDe($pagando, '-visor-pro');

        $this->get(route('viewer.show', $deEsa))->assertOk();

        // Y la otra ruta del visor, la de pedirlo. Son dos metodos distintos y
        // los dos cambiaron: cubrir uno y dar por bueno el otro es la version
        // pequena del mismo descuido.
        $this->actingAs($gratuita)->post(route('admin.projects.visor.pedir', $suyo));
        $this->assertNull($suyo->fresh()->viewer_requested_at);

        $this->actingAs($pagando)->post(route('admin.projects.visor.pedir', $deEsa));
        $this->assertNotNull($deEsa->fresh()->viewer_requested_at);
    }

    public function test_el_widget_incrustable_es_de_los_planes_de_pago(): void
    {
        // El widget queda fuera del PMV; se enciende para probar su puerta de
        // plan, que sigue ahi para cuando vuelva.
        config(['pmv.activo' => false]);

        $gratuita = $this->promotora(CompanyProfile::PLAN_STARTER);
        $suyo = $this->proyectoDe($gratuita);

        $this->get(route('embed.show', $suyo->slug))->assertNotFound();

        $pagando = $this->promotora(CompanyProfile::PLAN_PROFESSIONAL);
        $deEsa = $this->proyectoDe($pagando, '-pro');

        $this->get(route('embed.show', $deEsa->slug))->assertOk();
    }
}
