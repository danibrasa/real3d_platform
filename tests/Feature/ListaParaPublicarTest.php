<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Unit;
use App\Models\User;
use App\Support\Publicacion\ListaParaPublicar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Publicar deja de ser un silencio.
 *
 * Antes la promotora cambiaba el estado a "publico", la web le respondia
 * "Proyecto actualizado" y el proyecto se quedaba en borrador, porque `status`
 * no estaba entre los campos que se le permitian. Y si llegaba a publicarse sin
 * coordenadas, no salia en el portal y tampoco lo decia nadie.
 */
class ListaParaPublicarTest extends TestCase
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
            'company_name' => 'Promotora Publicadora',
            'slug' => 'promotora-publicadora',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);

        $this->proyecto = Project::create([
            'name' => 'Residencial Sin Montar',
            'slug' => 'residencial-sin-montar',
            'status' => 'draft',
            'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($this->proyecto->id);
    }

    private function subir(string $tipo): void
    {
        ProjectFile::create([
            'project_id' => $this->proyecto->id,
            'file_type' => $tipo,
            'original_name' => $tipo.'.bin',
            'storage_path' => 'projects/'.$this->proyecto->id.'/'.$tipo,
            'mime_type' => 'application/octet-stream',
            'file_size' => 1024,
            'upload_complete' => true,
        ]);
    }

    private function publicar()
    {
        return $this->actingAs($this->promotora)
            ->put(route('admin.projects.update', $this->proyecto), [
                'name' => $this->proyecto->name,
                'status' => 'public',
            ]);
    }

    // --- Sin nada que enseñar, no sale ------------------------------------

    public function test_un_proyecto_sin_visor_no_se_publica(): void
    {
        $this->publicar();

        $this->assertSame('draft', $this->proyecto->fresh()->status);
    }

    public function test_y_se_le_explica_por_que(): void
    {
        // Lo que fallaba antes no era que no publicara: era que no lo dijera.
        $this->publicar()->assertSessionHas('error');
    }

    // --- Con visor montado, publica sola ----------------------------------

    public function test_con_el_visor_montado_publica_sin_pedir_permiso(): void
    {
        $this->subir('video_360');
        $this->subir('model_3d');

        $this->publicar();

        $this->assertSame('public', $this->proyecto->fresh()->status);
    }

    public function test_un_fondo_360_ya_basta_para_publicar(): void
    {
        // El modelo 3D es lo deseable, pero un 360 solo ya enseña algo.
        $this->subir('image_360');

        $this->publicar();

        $this->assertSame('public', $this->proyecto->fresh()->status);
    }

    public function test_una_subida_a_medias_no_cuenta(): void
    {
        // Los videos 360 se suben por trozos: uno sin terminar no es un visor.
        ProjectFile::create([
            'project_id' => $this->proyecto->id,
            'file_type' => 'video_360',
            'original_name' => 'a-medias.mp4',
            'storage_path' => 'projects/x/video',
            'mime_type' => 'video/mp4',
            'file_size' => 5242880,
            'upload_complete' => false,
        ]);

        $this->publicar();

        $this->assertSame('draft', $this->proyecto->fresh()->status);
    }

    // --- Los avisos -------------------------------------------------------

    public function test_avisa_de_que_sin_coordenadas_no_sale_en_el_portal(): void
    {
        $this->subir('video_360');

        $claves = array_column(ListaParaPublicar::de($this->proyecto)->avisos(), 'clave');

        $this->assertContains('sin_coordenadas', $claves);
        $this->assertContains('sin_viviendas', $claves);
        $this->assertContains('sin_contacto', $claves);
    }

    public function test_lo_que_ya_esta_resuelto_deja_de_avisar(): void
    {
        $this->subir('video_360');
        $this->subir('model_3d');
        $this->subir('thumbnail');
        $this->proyecto->update([
            'latitude' => 18.58,
            'longitude' => -68.40,
            'contact_email' => 'ventas@promotora.com',
        ]);
        // Situada en el 3D: una vivienda sin caja es un aviso (tocarla no abre nada).
        Unit::create([
            'project_id' => $this->proyecto->id, 'identifier' => 'A-101',
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85,
            'price' => 185000, 'status' => 'available', 'sort_order' => 1,
            'bbox_center_x' => 0.5, 'bbox_center_y' => 0.5, 'bbox_center_z' => 0.5,
            'bbox_size_x' => 0.1, 'bbox_size_y' => 0.1, 'bbox_size_z' => 0.1,
        ]);

        $lista = ListaParaPublicar::de($this->proyecto->fresh());

        $this->assertTrue($lista->puedePublicarse());
        $this->assertSame([], $lista->avisos());
    }

    // --- Cada cosa, a quien le toca ---------------------------------------

    public function test_a_la_promotora_no_se_le_pide_montar_el_3d(): void
    {
        $puntos = array_merge(
            ListaParaPublicar::de($this->proyecto)->bloqueos(),
            ListaParaPublicar::de($this->proyecto)->avisos(),
        );

        $de = collect($puntos)->pluck('de', 'clave');

        $this->assertSame(ListaParaPublicar::EQUIPO, $de['sin_visor']);
        $this->assertSame(ListaParaPublicar::PROMOTORA, $de['sin_coordenadas']);
        $this->assertSame(ListaParaPublicar::PROMOTORA, $de['sin_viviendas']);
    }

    // --- Despublicar siempre se puede -------------------------------------

    public function test_puede_volver_a_borrador_aunque_falte_de_todo(): void
    {
        $this->subir('video_360');
        $this->proyecto->update(['status' => 'public']);

        $this->actingAs($this->promotora)
            ->put(route('admin.projects.update', $this->proyecto), [
                'name' => $this->proyecto->name,
                'status' => 'draft',
            ]);

        $this->assertSame('draft', $this->proyecto->fresh()->status);
    }
}
