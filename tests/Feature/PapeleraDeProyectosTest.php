<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Una promotora puede borrar sus proyectos, y arrepentirse durante 30 dias.
 *
 * Borrar era solo del equipo, y una promotora del plan gratuito (un
 * proyecto) no podia quitar el suyo para crear otro. Ahora puede, y como
 * puede hace falta poder deshacerlo: un clic de mas no puede costar un
 * visor que tardo dias en montarse. A los treinta dias se borra del todo,
 * ficheros incluidos, y la cuota vuelve.
 */
class PapeleraDeProyectosTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $otra;

    private Project $suyo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        $this->ana = $this->promotora('ana');
        $this->otra = $this->promotora('otra');

        $this->suyo = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => $this->ana->id,
        ]);
        $this->ana->assignedProjects()->attach($this->suyo->id);

        Storage::put("projects/{$this->suyo->id}/fondo.png", 'un fondo');
        ProjectFile::create([
            'project_id' => $this->suyo->id,
            'file_type' => 'image_360',
            'original_name' => 'fondo.png',
            'storage_path' => "projects/{$this->suyo->id}/fondo.png",
            'mime_type' => 'image/png',
            'file_size' => 5_000_000,
            'upload_complete' => true,
        ]);
        $this->ana->companyProfile->recalculateStorage();
    }

    private function promotora(string $nombre): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora de '.$nombre,
            'slug' => 'promotora-de-'.$nombre,
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => 1,
            'max_storage_bytes' => 10_000_000,
        ]);

        return $user->fresh();
    }

    public function test_la_promotora_borra_el_suyo_y_va_a_la_papelera(): void
    {
        $this->actingAs($this->ana)
            ->delete(route('admin.projects.destroy', $this->suyo))
            ->assertRedirect(route('admin.projects.index'));

        $this->assertSoftDeleted('projects', ['id' => $this->suyo->id]);

        // Desaparece de todas partes menos de la papelera.
        $this->get(route('viewer.landing', $this->suyo->slug))->assertNotFound();
        $this->actingAs($this->ana)->get(route('admin.projects.index'))
            ->assertOk()
            ->assertDontSee('Residencial Bahia')
            ->assertSee(route('admin.projects.papelera'), false);
        $this->actingAs($this->ana)->get(route('admin.projects.papelera'))
            ->assertOk()
            ->assertSee('Residencial Bahia');

        // Los ficheros siguen ahi hasta que caduque: si se arrepiente, vuelve todo.
        Storage::assertExists("projects/{$this->suyo->id}/fondo.png");

        // Y el hueco del plan queda libre: es para lo que borra.
        $this->assertTrue($this->ana->companyProfile->fresh()->canCreateProject());
        $this->assertSame(0, $this->ana->companyProfile->fresh()->storage_used_bytes);
    }

    public function test_no_se_borra_lo_ajeno(): void
    {
        $this->actingAs($this->otra)
            ->delete(route('admin.projects.destroy', $this->suyo))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $this->suyo->id, 'deleted_at' => null]);
    }

    public function test_se_recupera_entero(): void
    {
        $this->actingAs($this->ana)->delete(route('admin.projects.destroy', $this->suyo));

        $this->actingAs($this->ana)
            ->post(route('admin.projects.restaurar', $this->suyo->id))
            ->assertRedirect(route('admin.projects.edit', $this->suyo));

        $this->assertDatabaseHas('projects', ['id' => $this->suyo->id, 'deleted_at' => null]);
        $this->get(route('viewer.landing', $this->suyo->slug))->assertOk();
        $this->assertSame(5_000_000, $this->ana->companyProfile->fresh()->storage_used_bytes);
    }

    public function test_otra_promotora_no_recupera_lo_que_no_es_suyo(): void
    {
        $this->actingAs($this->ana)->delete(route('admin.projects.destroy', $this->suyo));

        $this->actingAs($this->otra)
            ->post(route('admin.projects.restaurar', $this->suyo->id))
            ->assertForbidden();

        $this->assertSoftDeleted('projects', ['id' => $this->suyo->id]);
    }

    public function test_a_los_treinta_dias_se_borra_del_todo_con_sus_ficheros(): void
    {
        $this->actingAs($this->ana)->delete(route('admin.projects.destroy', $this->suyo));

        // A los 29 dias, sigue.
        $this->travel(29)->days();
        $this->artisan('proyectos:vaciar-papelera')->assertSuccessful();
        $this->assertSoftDeleted('projects', ['id' => $this->suyo->id]);
        Storage::assertExists("projects/{$this->suyo->id}/fondo.png");

        // A los 31, se fue del todo, ficheros incluidos.
        $this->travel(2)->days();
        $this->artisan('proyectos:vaciar-papelera')->assertSuccessful();
        $this->assertDatabaseMissing('projects', ['id' => $this->suyo->id]);
        Storage::assertMissing("projects/{$this->suyo->id}/fondo.png");
    }

    public function test_el_vaciado_esta_en_la_agenda(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command ?? '', 'proyectos:vaciar-papelera'));

        $this->assertNotNull($evento, 'nadie vacia la papelera: se llenaria para siempre');
    }

    public function test_un_proyecto_nuevo_no_choca_con_el_slug_de_uno_en_la_papelera(): void
    {
        // El slug es unico en la tabla y el de la papelera sigue ahi. El
        // generador tiene que verlo, o el segundo "Residencial Bahia" revienta.
        $this->actingAs($this->ana)->delete(route('admin.projects.destroy', $this->suyo));

        $nuevo = Project::create([
            'name' => 'Residencial Bahia',
            'status' => 'draft',
            'created_by' => $this->ana->id,
        ]);

        $this->assertNotSame($this->suyo->slug, $nuevo->slug);
    }
}
