<?php

namespace Tests\Feature;

use App\Mail\InquiryAutoReply;
use App\Mail\NewInquiryNotification;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Una consulta desde el visor tiene que llegar a su promotora y poder contestarse.
 *
 * Es el momento por el que existe el producto: alguien mira una vivienda y
 * pregunta. Si ese aviso no llega, o llega y no se puede responder, da igual lo
 * bueno que sea el visor.
 */
class LeadTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create([
            'role' => 'inmobiliaria',
            'email' => 'promotora@ejemplo.com',
        ]);

        CompanyProfile::create([
            'user_id' => $this->promotora->id,
            'company_name' => 'Promotora de prueba',
            'slug' => 'promotora-de-prueba',
            'plan_tier' => CompanyProfile::PLAN_STARTER,
            'max_projects' => 5,
        ]);

        $this->proyecto = Project::create([
            'name' => 'Residencial de prueba',
            'slug' => 'residencial-de-prueba',
            'status' => 'public',
            'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($this->proyecto->id);
    }

    private function preguntar(): void
    {
        $this->post(route('viewer.inquiry', $this->proyecto), [
            'name' => 'Comprador Interesado',
            'email' => 'comprador@ejemplo.com',
            'phone' => '+1 809 000 0000',
            'message' => 'Me interesa una unidad de dos habitaciones.',
        ]);
    }

    public function test_el_aviso_llega_a_la_promotora_aunque_no_haya_correo_de_contacto(): void
    {
        Mail::fake();

        // El caso que se olvida al dar de alta un proyecto.
        $this->assertNull($this->proyecto->contact_email);

        $this->preguntar();

        Mail::assertQueued(NewInquiryNotification::class, function ($correo) {
            return $correo->hasTo('promotora@ejemplo.com');
        });
    }

    public function test_si_hay_correo_de_contacto_manda_ese(): void
    {
        Mail::fake();
        $this->proyecto->update(['contact_email' => 'ventas@promotora.com']);

        $this->preguntar();

        Mail::assertQueued(NewInquiryNotification::class, function ($correo) {
            return $correo->hasTo('ventas@promotora.com');
        });
    }

    public function test_la_promotora_puede_responder_al_comprador(): void
    {
        Mail::fake();
        $this->preguntar();

        Mail::assertQueued(NewInquiryNotification::class, function ($correo) {
            // Sin esto, darle a Responder escribe a noreply@real3d.io.
            return $correo->hasReplyTo('comprador@ejemplo.com');
        });
    }

    public function test_el_comprador_puede_responder_al_acuse_de_recibo(): void
    {
        Mail::fake();
        $this->proyecto->update(['contact_email' => 'ventas@promotora.com']);

        $this->preguntar();

        Mail::assertQueued(InquiryAutoReply::class, function ($correo) {
            return $correo->hasTo('comprador@ejemplo.com')
                && $correo->hasReplyTo('ventas@promotora.com');
        });
    }
}
