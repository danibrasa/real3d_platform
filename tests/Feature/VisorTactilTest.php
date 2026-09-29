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
 * Lo del visor que se ve desde PHP: que la pagina lleve lo que el modulo de
 * eleccion necesita, y que hable a cada mano en su idioma. La eleccion en
 * si se prueba con node en tools/probar-visor-eleccion.mjs.
 */
class VisorTactilTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_del_visor_explica_que_se_toca_una_vivienda_con_raton_y_con_dedo(): void
    {
        Storage::fake();
        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $promotora->id, 'company_name' => 'Promotora Bahia', 'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL, 'max_projects' => 5,
        ]);
        $proyecto = Project::create([
            'name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'public', 'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($proyecto->id);
        Storage::put('x/fondo.jpg', 'fondo');
        ProjectFile::create([
            'project_id' => $proyecto->id, 'file_type' => 'image_360', 'original_name' => 'fondo.jpg',
            'storage_path' => 'x/fondo.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 5, 'upload_complete' => true,
        ]);

        $this->get(route('viewer.show', $proyecto))
            ->assertOk()
            ->assertSee('toca una vivienda', false)
            ->assertSee('haz clic en una vivienda', false)
            ->assertDontSee('Click izq + arrastrar', false);

        // El modulo que elige la vivienda tiene que estar donde el visor lo importa.
        $this->assertFileExists(public_path('js/visor-eleccion.js'));
        $this->assertStringContainsString("from './visor-eleccion.js'", file_get_contents(public_path('js/viewer-public.js')));
    }
}
