<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La bandeja de consultas desde el telefono. El aviso del lead llega al
 * movil y desde ahi se contesta: la tabla de siete columnas no cabe en 390
 * px y lo que quedaba fuera era justo el estado y el "Ver". Lo que mide de
 * verdad (que no desborde, que se toque con el dedo) lo mira
 * tools/recorrido-panel.mjs en la nocturna; aqui, que el marcado que ese
 * recorrido espera este y lleve lo que hace falta.
 */
class BandejaMovilTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private Inquiry $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create(['user_id' => $this->ana->id, 'company_name' => 'Ana', 'slug' => 'ana', 'plan_tier' => CompanyProfile::PLAN_STARTER, 'max_projects' => 5]);
        $proyecto = Project::create(['name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'public', 'created_by' => $this->ana->id]);
        $this->ana->assignedProjects()->attach($proyecto->id);
        $this->lead = Inquiry::create([
            'project_id' => $proyecto->id, 'name' => 'Comprador Interesado', 'email' => 'comprador@ejemplo.invalid',
            'phone' => '+1 809 555 0100', 'message' => 'Me interesa la A-102.', 'read' => false,
        ]);
    }

    public function test_la_bandeja_lleva_tarjetas_para_el_telefono_con_whatsapp_estado_y_ver(): void
    {
        $html = $this->actingAs($this->ana)->get(route('admin.inquiries.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div class="sm:hidden[^"]*" data-tarjetas>/', $html, 'las tarjetas solo en el telefono');
        $tarjetas = substr($html, strpos($html, 'data-tarjetas'));
        $tarjetas = substr($tarjetas, 0, strpos($tarjetas, '<table'));

        $this->assertStringContainsString('Comprador Interesado', $tarjetas);
        $this->assertStringContainsString('href="https://wa.me/18095550100?text=', $tarjetas, 'el WhatsApp con el saludo');
        $this->assertStringContainsString('href="tel:+1 809 555 0100"', $tarjetas);
        $this->assertStringContainsString(route('admin.inquiries.estado', $this->lead), $tarjetas, 'el estado se cambia desde la tarjeta');
        $this->assertStringContainsString(route('admin.inquiries.show', $this->lead), $tarjetas);
        $this->assertStringContainsString('border-blue-500', $tarjetas, 'la no leida se distingue');

        // Y la tabla sigue para el escritorio, pero ya no recorta lo que no
        // cabe: se desplaza.
        $this->assertMatchesRegularExpression('/<div class="[^"]*hidden sm:block[^"]*overflow-x-auto[^"]*">\s*<table/', $html);
    }

    public function test_la_ficha_del_lead_tiene_el_whatsapp_a_tamano_de_dedo(): void
    {
        $this->actingAs($this->ana)->get(route('admin.inquiries.show', $this->lead))
            ->assertOk()
            ->assertSee('href="https://wa.me/18095550100?text=', false)
            ->assertSee('py-2.5 rounded-md bg-emerald-600', false);
    }
}
