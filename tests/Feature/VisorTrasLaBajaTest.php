<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Que darse de baja quite el visor, y solo el visor.
 *
 * Cerrar quien puede PEDIR un visor no bastaba. Una vez montado y publicado, lo
 * publico solo miraba el estado del proyecto: se podia contratar, esperar a que
 * lo montaramos, darse de baja y quedarselo funcionando para siempre. Con la
 * prueba de catorce dias, ni siquiera hacia falta llegar a pagar una factura.
 *
 * Y habia dos puertas, no una: la pagina del visor y la API por la que salen el
 * modelo y el fondo. Cerrar solo la primera dejaba el 3D descargable con la
 * direccion, que es medio muro y por tanto ninguno.
 *
 * Lo que NO se toca es el plan gratuito: la pagina sigue publicada, con sus
 * viviendas y su formulario de contacto. Se pierde lo que se dejo de pagar.
 */
class VisorTrasLaBajaTest extends TestCase
{
    use RefreshDatabase;

    private function proyectoPublicadoDe(string $plan): Project
    {
        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);

        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia-'.$promotora->id,
            'plan_tier' => $plan,
            'max_projects' => 5,
        ]);

        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia-'.$promotora->id,
            'status' => 'public',
            'created_by' => $promotora->id,
        ]);

        $promotora->assignedProjects()->attach($proyecto->id);

        ProjectFile::create([
            'project_id' => $proyecto->id,
            'file_type' => 'image_360',
            'original_name' => 'fondo.png',
            'storage_path' => 'x/fondo.png',
            'mime_type' => 'image/png',
            'file_size' => 100,
            'upload_complete' => true,
        ]);

        return $proyecto->fresh();
    }

    public function test_con_plan_de_pago_el_visor_se_sirve(): void
    {
        $proyecto = $this->proyectoPublicadoDe(CompanyProfile::PLAN_PROFESSIONAL);

        $this->get(route('viewer.show', $proyecto))->assertOk();
    }

    public function test_tras_la_baja_el_visor_lleva_a_la_ficha(): void
    {
        $proyecto = $this->proyectoPublicadoDe(CompanyProfile::PLAN_STARTER);

        // No es un 404: quien mira es un comprador que no tiene culpa de nada,
        // y en la ficha encuentra el proyecto, los precios y el contacto.
        $this->get(route('viewer.show', $proyecto))
            ->assertRedirect(route('viewer.landing', $proyecto));
    }

    public function test_tras_la_baja_el_modelo_tampoco_se_descarga(): void
    {
        $proyecto = $this->proyectoPublicadoDe(CompanyProfile::PLAN_STARTER);

        // La ruta no tiene nombre, asi que se pide por su direccion, que es
        // ademas como la pediria quien se la quiera descargar.
        foreach (['model_3d', 'image_360', 'video_360'] as $tipo) {
            $this->get('/api/projects/'.$proyecto->getRouteKey().'/files/'.$tipo)
                ->assertNotFound();
        }
    }

    public function test_con_plan_de_pago_el_modelo_si_se_sirve(): void
    {
        // La mitad simetrica: un 404 para todo el mundo tambien pasaria el test
        // de arriba, y habria roto el visor de quien si paga.
        //
        // Con el fichero puesto de verdad en un disco de mentira, la respuesta
        // buena es un 200. Distinguir el 404 del muro del 404 del disco por el
        // mensaje era mas fragil y decia menos.
        Storage::fake();
        $proyecto = $this->proyectoPublicadoDe(CompanyProfile::PLAN_PROFESSIONAL);
        Storage::put('x/fondo.png', 'un fondo cualquiera');

        $this->get('/api/projects/'.$proyecto->getRouteKey().'/files/image_360')
            ->assertOk();
    }

    public function test_la_ficha_publica_sigue_funcionando_tras_la_baja(): void
    {
        // Esto es lo que NO se pierde: es el plan gratuito, y lo tiene ganado.
        $proyecto = $this->proyectoPublicadoDe(CompanyProfile::PLAN_STARTER);

        $this->get(route('viewer.landing', $proyecto))->assertOk();
    }

    public function test_a_la_promotora_se_le_dice_que_su_visor_esta_apagado(): void
    {
        // Lo peor no era perder el visor: era perderlo sin enterarse. La pagina
        // sigue en pie y da la impresion de que todo va bien, asi que se entera
        // cuando se lo dice un comprador, o no se entera nunca.
        $proyecto = $this->proyectoPublicadoDe(CompanyProfile::PLAN_STARTER);
        $promotora = $proyecto->assignedAgencies()->first();

        $this->actingAs($promotora)
            ->get(route('admin.projects.edit', $proyecto))
            ->assertOk()
            ->assertSee(__('publicacion.visor_apagado'));
    }

    public function test_con_plan_de_pago_no_se_le_dice_nada_de_eso(): void
    {
        // Un aviso que sale siempre es ruido, y el ruido se deja de leer.
        $proyecto = $this->proyectoPublicadoDe(CompanyProfile::PLAN_PROFESSIONAL);
        $promotora = $proyecto->assignedAgencies()->first();

        $this->actingAs($promotora)
            ->get(route('admin.projects.edit', $proyecto))
            ->assertOk()
            ->assertDontSee(__('publicacion.visor_apagado'));
    }

    public function test_un_proyecto_sin_promotora_se_sigue_sirviendo(): void
    {
        // Los nuestros -- demos, portada -- no tienen suscripcion que caducar.
        // Cerrarlos por no encontrarles plan romperia la portada entera.
        $equipo = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $proyecto = Project::create([
            'name' => 'Demo de Real3D',
            'slug' => 'demo-real3d',
            'status' => 'public',
            'created_by' => $equipo->id,
        ]);

        $this->get(route('viewer.show', $proyecto))->assertOk();
    }
}
