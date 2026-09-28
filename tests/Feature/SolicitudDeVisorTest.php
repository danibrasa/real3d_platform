<?php

namespace Tests\Feature;

use App\Mail\SolicitudDeVisor;
use App\Mail\VisorMontado;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * La costura entre lo que hace la promotora y lo que hace el equipo.
 *
 * Con el reparto hibrido, la promotora carga sus viviendas y el equipo monta el
 * 3D. Faltaba el paso del medio: ella terminaba y leia "lo hace el equipo de
 * Real3D" sin ningun boton, y el equipo no tenia lista de quien espera.
 */
class SolicitudDeVisorTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);

        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia', 'slug' => 'residencial-bahia',
            'status' => 'draft', 'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($this->proyecto->id);
    }

    public function test_la_promotora_puede_avisar_de_que_esta_lista(): void
    {
        Mail::fake();

        $this->actingAs($this->promotora)
            ->post(route('admin.projects.visor.pedir', $this->proyecto))
            ->assertRedirect();

        $this->assertNotNull($this->proyecto->fresh()->viewer_requested_at);
        $this->assertSame($this->promotora->id, $this->proyecto->fresh()->viewer_requested_by);
    }

    public function test_el_equipo_recibe_el_aviso(): void
    {
        Mail::fake();
        $jefe = User::factory()->create(['role' => 'superadmin', 'email' => 'equipo@real3d.io']);

        $this->actingAs($this->promotora)
            ->post(route('admin.projects.visor.pedir', $this->proyecto));

        Mail::assertQueued(SolicitudDeVisor::class, fn ($c) => $c->hasTo($jefe->email));
    }

    public function test_el_equipo_puede_responderle_directamente(): void
    {
        Mail::fake();
        User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($this->promotora)
            ->post(route('admin.projects.visor.pedir', $this->proyecto));

        // Para poder pedirle los planos sin buscar su correo en ningun sitio.
        Mail::assertQueued(
            SolicitudDeVisor::class,
            fn ($c) => $c->hasReplyTo($this->promotora->email)
        );
    }

    public function test_pedirlo_dos_veces_no_reinicia_la_espera(): void
    {
        Mail::fake();

        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));
        $primera = $this->proyecto->fresh()->viewer_requested_at;

        $this->travel(2)->days();
        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));

        // Si se reiniciara, quien lleva mas esperando dejaria de salir el primero
        // en la cola del equipo, que es justo para lo que sirve esa pantalla.
        $this->assertEquals($primera, $this->proyecto->fresh()->viewer_requested_at);
        Mail::assertQueuedCount(0 + Mail::queued(SolicitudDeVisor::class)->count());
    }

    public function test_puede_retirar_la_solicitud(): void
    {
        Mail::fake();
        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));

        $this->actingAs($this->promotora)
            ->delete(route('admin.projects.visor.retirar', $this->proyecto));

        $this->assertNull($this->proyecto->fresh()->viewer_requested_at);
    }

    public function test_no_puede_pedir_visor_para_un_proyecto_ajeno(): void
    {
        Mail::fake();
        $otra = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $otra->id, 'company_name' => 'Otra', 'slug' => 'otra',
            'plan_tier' => CompanyProfile::PLAN_STARTER, 'max_projects' => 1,
        ]);

        $this->actingAs($otra)
            ->post(route('admin.projects.visor.pedir', $this->proyecto))
            ->assertForbidden();

        $this->assertNull($this->proyecto->fresh()->viewer_requested_at);
    }

    // --- La cola del equipo ------------------------------------------------

    public function test_el_equipo_ve_la_cola_ordenada_por_antiguedad(): void
    {
        Mail::fake();
        $jefe = User::factory()->create(['role' => 'superadmin']);

        $viejo = Project::create([
            'name' => 'El que lleva mas esperando', 'slug' => 'el-viejo',
            'status' => 'draft', 'created_by' => $this->promotora->id,
            'viewer_requested_at' => now()->subDays(9),
        ]);
        $this->promotora->assignedProjects()->attach($viejo->id);

        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));

        $respuesta = $this->actingAs($jefe)->get(route('admin.visores.pendientes'))->assertOk();

        $html = $respuesta->getContent();
        $this->assertLessThan(
            strpos($html, 'Residencial Bahia'),
            strpos($html, 'El que lleva mas esperando'),
            'El que lleva mas esperando tiene que salir antes'
        );
    }

    public function test_una_promotora_no_ve_la_cola_del_equipo(): void
    {
        // Ahi se ven los proyectos de todas las promotoras.
        $this->actingAs($this->promotora)
            ->get(route('admin.visores.pendientes'))
            ->assertForbidden();
    }

    public function test_los_que_no_han_pedido_nada_no_salen_en_la_cola(): void
    {
        $jefe = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($jefe)
            ->get(route('admin.visores.pendientes'))
            ->assertOk()
            ->assertDontSee('Residencial Bahia');
    }

    public function test_el_correo_se_puede_pintar_de_verdad(): void
    {
        // Mail::fake() no renderiza la plantilla, asi que los tests de arriba
        // pasaban en verde con el correo roto: declaraba `view` cuando la
        // plantilla usa componentes de markdown. Esto lo pinta.
        $correo = new SolicitudDeVisor($this->proyecto, $this->promotora);

        $html = $correo->render();

        $this->assertStringContainsString($this->proyecto->name, $html);
        $this->assertStringContainsString($this->promotora->name, $html);
    }

    // --- El equipo cierra el circulo --------------------------------------

    private function montarVisor(): void
    {
        ProjectFile::create([
            'project_id' => $this->proyecto->id,
            'file_type' => 'image_360',
            'original_name' => 'fondo.png',
            'storage_path' => 'x/fondo.png',
            'mime_type' => 'image/png',
            'file_size' => 100,
            'upload_complete' => true,
        ]);
    }

    public function test_el_equipo_da_por_montado_el_visor(): void
    {
        Mail::fake();
        $jefe = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));
        $this->montarVisor();

        $this->actingAs($jefe)
            ->post(route('admin.projects.visor.montado', $this->proyecto))
            ->assertRedirect();

        // Sale de la cola: si se quedara, la lista solo creceria.
        $this->assertNull($this->proyecto->fresh()->viewer_requested_at);
    }

    public function test_se_avisa_a_quien_lo_pidio(): void
    {
        Mail::fake();
        $jefe = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));
        $this->montarVisor();

        $this->actingAs($jefe)->post(route('admin.projects.visor.montado', $this->proyecto));

        Mail::assertQueued(
            VisorMontado::class,
            fn ($c) => $c->hasTo($this->promotora->email)
        );
    }

    public function test_no_se_da_por_montado_lo_que_no_esta_montado(): void
    {
        // Avisar de que ya puede publicar sin haber subido nada la mandaria a
        // publicar una pagina vacia con su nombre encima.
        Mail::fake();
        $jefe = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));

        $this->actingAs($jefe)
            ->post(route('admin.projects.visor.montado', $this->proyecto))
            ->assertSessionHas('error');

        $this->assertNotNull($this->proyecto->fresh()->viewer_requested_at);
        Mail::assertNotQueued(VisorMontado::class);
    }

    public function test_una_promotora_no_puede_darlo_por_montado(): void
    {
        Mail::fake();
        $this->actingAs($this->promotora)->post(route('admin.projects.visor.pedir', $this->proyecto));
        $this->montarVisor();

        $this->actingAs($this->promotora)
            ->post(route('admin.projects.visor.montado', $this->proyecto))
            ->assertForbidden();

        $this->assertNotNull($this->proyecto->fresh()->viewer_requested_at);
    }

    public function test_el_correo_de_visor_montado_se_pinta(): void
    {
        $jefe = User::factory()->create(['role' => 'superadmin']);

        $html = (new VisorMontado($this->proyecto, $jefe))->render();

        $this->assertStringContainsString($this->proyecto->name, $html);
    }
}
