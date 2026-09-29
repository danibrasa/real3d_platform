<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Que la cuota de almacenamiento cuente lo que hay, no lo que hubo.
 *
 * El contador subia con cada subida y no bajaba al borrar un proyecto: una
 * promotora que quitara uno seguia "usando" sus ficheros, y con la cuota
 * llena no podia subir el visor del siguiente. Cobrarle sitio por ficheros
 * que ya no existen.
 */
class CuotaDeAlmacenamientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_borrar_un_proyecto_devuelve_su_sitio(): void
    {
        $promotora = User::factory()->create(['role' => 'inmobiliaria']);
        $perfil = CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora de prueba',
            'slug' => 'promotora-de-prueba',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 10,
            'max_storage_bytes' => 10_000_000,
        ]);

        $proyecto = Project::create([
            'name' => 'Residencial de prueba',
            'slug' => 'residencial-de-prueba',
            'status' => 'draft',
            'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($proyecto->id);

        ProjectFile::create([
            'project_id' => $proyecto->id,
            'file_type' => 'model_3d',
            'original_name' => 'modelo.glb',
            'storage_path' => 'projects/'.$proyecto->id.'/modelo.glb',
            'mime_type' => 'model/gltf-binary',
            'file_size' => 5_000_000,
            'upload_complete' => true,
        ]);
        // Un segundo proyecto que se queda: asi se distingue "recalcular con
        // lo que hay" de "poner a cero", que con un solo proyecto pasarian
        // el mismo test.
        $otro = Project::create([
            'name' => 'Residencial que se queda',
            'slug' => 'residencial-que-se-queda',
            'status' => 'draft',
            'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($otro->id);
        ProjectFile::create([
            'project_id' => $otro->id,
            'file_type' => 'image_360',
            'original_name' => 'fondo.jpg',
            'storage_path' => 'projects/'.$otro->id.'/fondo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1_500_000,
            'upload_complete' => true,
        ]);

        $perfil->recalculateStorage();
        $this->assertSame(6_500_000, $perfil->fresh()->storage_used_bytes);

        // Borrar proyectos es del equipo, no de la promotora.
        $equipo = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($equipo)->delete(route('admin.projects.destroy', $proyecto));

        $this->assertSoftDeleted('projects', ['id' => $proyecto->id]);
        $this->assertSame(1_500_000, $perfil->fresh()->storage_used_bytes,
            'el proyecto se fue y sus ficheros siguen contando en la cuota, o se llevo por delante los del otro');
    }
}
