<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Unit;
use App\Models\User;
use App\Support\Publicacion\ListaParaPublicar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Con modelo y viviendas sin caja, tocar el 3D no abre nada y parece roto.
 * La lista para publicar lo dice, como aviso del equipo.
 */
class ViviendasSinSituarTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        $equipo = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $this->proyecto = Project::create(['name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'draft', 'created_by' => $equipo->id]);
        Unit::create(['project_id' => $this->proyecto->id, 'identifier' => 'A-101', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 70, 'price' => 100000, 'status' => 'available', 'sort_order' => 1]);
    }

    private function avisos(): array
    {
        return array_column(ListaParaPublicar::de($this->proyecto->fresh())->avisos(), 'clave');
    }

    public function test_sin_modelo_no_se_avisa_de_nada_que_situar(): void
    {
        $this->assertNotContains('viviendas_sin_situar', $this->avisos());
    }

    public function test_con_modelo_y_una_vivienda_sin_caja_se_avisa_y_situarla_lo_quita(): void
    {
        ProjectFile::create([
            'project_id' => $this->proyecto->id, 'file_type' => 'model_3d', 'original_name' => 'm.glb',
            'storage_path' => 'x/m.glb', 'mime_type' => 'model/gltf-binary', 'file_size' => 5, 'upload_complete' => true,
        ]);

        $this->assertContains('viviendas_sin_situar', $this->avisos());
        $this->assertSame(ListaParaPublicar::EQUIPO, collect(ListaParaPublicar::de($this->proyecto->fresh())->avisos())->firstWhere('clave', 'viviendas_sin_situar')['de']);

        Unit::first()->update(['bbox_center_x' => 0.5, 'bbox_center_y' => 0.5, 'bbox_center_z' => 0.5, 'bbox_size_x' => 0.1, 'bbox_size_y' => 0.1, 'bbox_size_z' => 0.1]);

        $this->assertNotContains('viviendas_sin_situar', $this->avisos());
    }

    public function test_el_mapeador_enseña_el_progreso_y_el_boton_de_situar_por_nombre(): void
    {
        // Sin modelo el mapeador no ensena la lista: pide subirlo primero.
        ProjectFile::create([
            'project_id' => $this->proyecto->id, 'file_type' => 'model_3d', 'original_name' => 'm.glb',
            'storage_path' => 'x/m.glb', 'mime_type' => 'model/gltf-binary', 'file_size' => 5, 'upload_complete' => true,
        ]);

        // Una situada y otra no: el contador del servidor tiene que contar,
        // no salir siempre a cero.
        Unit::create(['project_id' => $this->proyecto->id, 'identifier' => 'A-102', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 70, 'price' => 100000, 'status' => 'available', 'sort_order' => 2,
            'bbox_center_x' => 0.5, 'bbox_center_y' => 0.5, 'bbox_center_z' => 0.5, 'bbox_size_x' => 0.1, 'bbox_size_y' => 0.1, 'bbox_size_z' => 0.1]);

        $this->actingAs(User::first())->get(route('admin.projects.unit-mapping', $this->proyecto))
            ->assertOk()
            ->assertSee('id="mapeadas"', false)
            ->assertSee('1/2', false)
            ->assertSee('id="btn-auto"', false);

        $this->assertFileExists(public_path('js/visor-mapeo.js'));
        $this->assertStringContainsString("from './visor-mapeo.js'", file_get_contents(public_path('js/viewer-bbox-mapper.js')));
    }
}
