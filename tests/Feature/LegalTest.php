<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo legal: que exista, que se lea, que se acepte y que quede constancia.
 *
 * Hasta hoy no habia ni una pagina legal ni una casilla en el registro. Sin
 * eso no se puede cobrar a nadie, y las analiticas del visor se cargaban a
 * todo el mundo sin preguntar.
 */
class LegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_tres_paginas_se_leen_en_los_dos_idiomas(): void
    {
        foreach (['privacidad', 'condiciones', 'cookies'] as $pagina) {
            $this->get(route('legal.'.$pagina, ['lang' => 'es']))
                ->assertOk()
                ->assertSee(config('legal.responsable'))
                ->assertSee(config('legal.correo'));

            $this->get(route('legal.'.$pagina, ['lang' => 'en']))
                ->assertOk()
                ->assertSee(__('legal.'.$pagina, [], 'en'));
        }
    }

    public function test_sin_aceptar_no_hay_cuenta(): void
    {
        foreach (['/register/business', '/register'] as $formulario) {
            $this->post($formulario, [
                'name' => 'Ana Promotora',
                'email' => 'ana'.md5($formulario).'@ejemplo.invalid',
                'password' => 'Contrasena-larga-1',
                'password_confirmation' => 'Contrasena-larga-1',
            ])->assertSessionHasErrors('acepto');

            $this->assertDatabaseMissing('users', ['email' => 'ana'.md5($formulario).'@ejemplo.invalid']);
        }
    }

    public function test_al_aceptar_queda_constancia_de_cuando_y_de_que_version(): void
    {
        $this->post('/register/business', [
            'name' => 'Ana Promotora',
            'email' => 'ana@ejemplo.invalid',
            'password' => 'Contrasena-larga-1',
            'password_confirmation' => 'Contrasena-larga-1',
            'acepto' => '1',
        ])->assertRedirect();

        $ana = User::where('email', 'ana@ejemplo.invalid')->firstOrFail();

        $this->assertNotNull($ana->legal_aceptado_en, 'acepto y no quedo la fecha');
        $this->assertSame(config('legal.version'), $ana->legal_version);
    }

    public function test_los_formularios_de_registro_enlazan_lo_que_se_acepta(): void
    {
        foreach ([route('register.business'), route('register')] as $formulario) {
            $this->get($formulario)
                ->assertOk()
                ->assertSee('name="acepto"', false)
                ->assertSee(route('legal.condiciones'), false)
                ->assertSee(route('legal.privacidad'), false);
        }
    }

    public function test_el_pie_legal_esta_en_todas_las_puertas(): void
    {
        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => $promotora->id,
        ]);

        foreach ([
            'portada' => '/',
            'portal' => route('portal.home'),
            'login' => route('login'),
            'ficha publica' => route('viewer.landing', $proyecto),
            'panel' => route('admin.dashboard'),
        ] as $puerta => $url) {
            $respuesta = $puerta === 'panel'
                ? $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]))->get($url)
                : $this->get($url);

            $respuesta->assertOk()->assertSee(route('legal.privacidad'), false);
        }
    }

    public function test_las_analiticas_del_visor_esperan_al_consentimiento(): void
    {
        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);
        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => $promotora->id,
            'analytics_id' => 'G-DEPRUEBA',
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

        $visor = $this->get(route('viewer.show', $proyecto))->assertOk();

        // La etiqueta de Google no va en el HTML: la pone un guion, y solo
        // despues de aceptar. Y el aviso esta, con su enlace a la politica.
        $visor->assertDontSee('<script async src="https://www.googletagmanager.com', false);
        $visor->assertSee('cargarAnaliticas', false);
        $visor->assertSee('id="aviso-cookies"', false);
        $visor->assertSee(route('legal.cookies'), false);
    }

    public function test_sin_analiticas_no_hay_aviso_que_molestar(): void
    {
        // Las cookies necesarias no piden permiso: un aviso en cada visor
        // para nada es el que todo el mundo cierra sin leer.
        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);
        $proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
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
        config(['services.google_analytics.id' => null]);

        $this->get(route('viewer.show', $proyecto))
            ->assertOk()
            ->assertDontSee('id="aviso-cookies"', false);
    }
}
