<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Unit;
use App\Models\UnitTypology;
use App\Models\User;
use App\Support\Agentes\Invitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * El panel habla de promotora a promotora: en español con sus acentos y sin
 * jerga nuestra ("Dashboard", "bbox", "Tagline", "solo logueados"). Se mira
 * el texto que ve una promotora en cada pantalla, no el codigo: lo que
 * cuenta es lo que lee.
 */
class PanelSinJergaTest extends TestCase
{
    use RefreshDatabase;

    /** Lo que no debe leer una promotora. Palabras enteras, con mayusculas tal cual. */
    private const PROHIBIDO = '/\b(Dashboard|Analytics|Bounding|bbox|Bbox|POIs|Tagline|logueados|settings|Widget|Unlimited|clicks|downloads|Website|Admin Panel'
        .'|Ubicacion|Descripcion|Tipologia|Tipologias|Banos|Suscripcion|Sesion|Publico|Publicos|Configuracion|Galeria|Telefono|Tamano|Contrasena'
        .'|Pagina|Direccion|Codigo|Razon|Pais|Estadisticas|Categoria|interes|dias|unicos|Duracion|Conversion|conversion|Mas|Area|Espana|Mexico|Republica)\b/u';

    private User $ana;

    private array $rutas;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->ana = User::factory()->create(['role' => User::ROLE_INMOBILIARIA, 'name' => 'Ana Promotora']);
        CompanyProfile::create(['user_id' => $this->ana->id, 'company_name' => 'Promotora Ana', 'slug' => 'ana', 'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL, 'max_projects' => 5]);
        $p = Project::create(['name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'draft', 'created_by' => $this->ana->id]);
        $this->ana->assignedProjects()->attach($p->id);
        $t = UnitTypology::create(['project_id' => $p->id, 'name' => 'Tipo A', 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 70]);
        $u = Unit::create(['project_id' => $p->id, 'identifier' => 'A-101', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 70, 'price' => 100000, 'status' => 'available', 'typology_id' => $t->id]);
        ProjectFile::create(['project_id' => $p->id, 'file_type' => 'model_3d', 'original_name' => 'm.glb', 'storage_path' => 'x/m.glb', 'mime_type' => 'model/gltf-binary', 'file_size' => 5, 'upload_complete' => true]);
        $i = Inquiry::create(['project_id' => $p->id, 'unit_id' => $u->id, 'name' => 'Comprador', 'email' => 'c@ejemplo.invalid', 'phone' => '+1 809 555 0100', 'message' => 'Hola', 'read' => false]);
        $ag = Invitacion::invitar($this->ana, 'Luis Comercial', 'luis@ejemplo.invalid');

        // Por su direccion y no por nombre de ruta: es lo que teclea el navegador.
        $this->rutas = [
            '/admin', '/admin/projects', '/admin/projects/create', "/admin/projects/{$p->id}/edit",
            "/admin/projects/{$p->id}/material", "/admin/projects/{$p->id}/location", '/admin/projects/papelera',
            "/admin/projects/{$p->id}/typologies", "/admin/projects/{$p->id}/typologies/create", "/admin/projects/{$p->id}/typologies/{$t->id}/edit",
            "/admin/projects/{$p->id}/units", "/admin/projects/{$p->id}/units/create", "/admin/projects/{$p->id}/units/{$u->id}/edit",
            "/admin/projects/{$p->id}/units/import",
            '/admin/inquiries', "/admin/inquiries/{$i->id}",
            '/admin/users', '/admin/users/create', "/admin/users/{$ag->id}/edit",
            '/admin/company-profile', '/admin/subscription', '/admin/analytics', '/admin/chatbot',
        ];
    }

    /** El texto que lee una persona: sin etiquetas, sin scripts, sin estilos. */
    private function textoVisible(string $html): string
    {
        $html = preg_replace('/<script\b.*?<\/script>|<style\b.*?<\/style>|<!--.*?-->/s', ' ', $html);
        $html = preg_replace('/<[^>]+>/', "\n", $html);

        // "Google Analytics" es el nombre del producto, no jerga nuestra.
        return str_replace('Google Analytics', 'GA', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function test_ninguna_pantalla_de_la_promotora_le_habla_en_jerga_ni_sin_acentos(): void
    {
        $fallos = [];
        foreach ($this->rutas as $ruta) {
            $respuesta = $this->actingAs($this->ana)->withHeaders(['Accept-Language' => 'es'])->get($ruta);
            $this->assertSame(200, $respuesta->getStatusCode(), $ruta);

            if (preg_match_all(self::PROHIBIDO, $this->textoVisible($respuesta->getContent()), $m)) {
                $fallos[] = $ruta.': '.implode(', ', array_unique($m[1]));
            }
        }

        $this->assertSame([], $fallos, "Jerga o palabras sin acento a la vista de la promotora:\n".implode("\n", $fallos));
    }

    public function test_las_rutas_sin_pagina_dan_404_y_no_500(): void
    {
        $p = Project::first();
        // Sin "show" queda el PUT/DELETE de la misma direccion: un GET da 405. Lo que no puede dar es 500.
        $this->assertContains($this->actingAs($this->ana)->get('/admin/projects/'.$p->id)->getStatusCode(), [404, 405]);
        $this->assertContains($this->actingAs($this->ana)->get('/admin/projects/'.$p->id.'/units/'.Unit::first()->id)->getStatusCode(), [404, 405]);
    }

    public function test_la_promotora_no_ve_la_caja_3d_de_la_vivienda_ni_la_calculadora_de_inversion(): void
    {
        $p = Project::first();
        $this->actingAs($this->ana)->get(route('admin.projects.units.create', $p))->assertOk()
            ->assertDontSee('bbox_center_x');
        $this->actingAs($this->ana)->get(route('admin.projects.edit', $p))->assertOk()
            ->assertDontSee('avg_nightly_rate');
    }
}
