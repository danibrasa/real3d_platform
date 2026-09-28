<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un lead se contesta, no se mira.
 *
 * El telefono se capturaba desde el principio y no aparecia en el listado, asi
 * que para escribirle a alguien habia que entrar en su ficha y copiarlo a mano.
 * En Republica Dominicana el negocio se hace por WhatsApp: si contestar cuesta
 * tres pasos, se contesta mas tarde, y un lead contestado mas tarde vale menos.
 */
class ContactarLeadTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private Inquiry $consulta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora',
            'slug' => 'promotora',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);

        $proyecto = Project::create([
            'name' => 'Residencial Bahia', 'slug' => 'residencial-bahia',
            'status' => 'public', 'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($proyecto->id);

        $this->consulta = Inquiry::create([
            'project_id' => $proyecto->id,
            'name' => 'Comprador Interesado',
            'email' => 'comprador@ejemplo.com',
            'phone' => '+1 809 555 0100',
            'message' => 'Me interesa una unidad de dos habitaciones.',
            'read' => false,
        ]);
    }

    public function test_el_telefono_se_ve_en_el_listado(): void
    {
        // Se capturaba y no se enseñaba: para llamar habia que abrir la ficha.
        $this->actingAs($this->promotora)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('+1 809 555 0100');
    }

    public function test_desde_el_listado_se_puede_escribir_por_whatsapp(): void
    {
        $this->actingAs($this->promotora)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            // El numero va sin simbolos ni espacios, que es lo que acepta wa.me.
            ->assertSee('https://wa.me/18095550100', false);
    }

    public function test_en_la_ficha_el_mensaje_viene_empezado(): void
    {
        // Que no haya que pensar que escribir es la diferencia entre contestar
        // ahora y contestar manana.
        $this->actingAs($this->promotora)
            ->get(route('admin.inquiries.show', $this->consulta))
            ->assertOk()
            ->assertSee('wa.me/18095550100', false)
            ->assertSee(urlencode('Comprador Interesado'), false);
    }

    public function test_sin_telefono_no_se_ofrece_whatsapp(): void
    {
        // El telefono no es obligatorio en el formulario publico.
        $this->consulta->update(['phone' => null]);

        $this->actingAs($this->promotora)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertDontSee('wa.me/', false);
    }

    public function test_una_promotora_no_ve_los_leads_de_otra(): void
    {
        // Datos de personas, y ademas informacion comercial de la competencia.
        $otra = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $otra->id, 'company_name' => 'Otra', 'slug' => 'otra',
            'plan_tier' => CompanyProfile::PLAN_STARTER, 'max_projects' => 1,
        ]);

        $this->actingAs($otra)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertDontSee('comprador@ejemplo.com');
    }
}
