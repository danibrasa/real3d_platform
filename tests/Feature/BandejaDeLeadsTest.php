<?php

namespace Tests\Feature;

use App\Mail\LeadsSinAtender;
use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * La bandeja de compradores: estados, notas, y el aviso de los que se quedan sin contestar.
 *
 * "Leido" no decia nada de lo que importa: si alguien le ha escrito, si va a
 * visitar, si compro o si era ruido. Y un lead contestado al dia siguiente
 * vale la mitad; a los tres dias, nada.
 */
class BandejaDeLeadsTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private User $otra;

    private Inquiry $lead;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->ana = $this->promotora('ana');
        $this->otra = $this->promotora('otra');

        $proyecto = Project::create([
            'name' => 'Residencial Bahia', 'slug' => 'residencial-bahia',
            'status' => 'public', 'created_by' => $this->ana->id,
            'contact_email' => 'ventas@ana.invalid',
        ]);
        $this->ana->assignedProjects()->attach($proyecto->id);

        $this->lead = Inquiry::create([
            'project_id' => $proyecto->id,
            'name' => 'Comprador Interesado',
            'email' => 'comprador@ejemplo.invalid',
            'phone' => '+1 809 555 0100',
            'message' => 'Me interesa la A-102.',
            'read' => false,
        ]);
    }

    private function promotora(string $nombre): User
    {
        $user = User::factory()->create(['role' => User::ROLE_INMOBILIARIA, 'email' => $nombre.'@ejemplo.invalid']);
        CompanyProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Promotora de '.$nombre,
            'slug' => 'promotora-de-'.$nombre,
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);

        return $user->fresh();
    }

    public function test_nace_nuevo_y_pasa_por_los_estados_con_quien_y_cuando(): void
    {
        $this->assertSame(Inquiry::NUEVO, $this->lead->fresh()->estado);

        $this->actingAs($this->ana)
            ->patch(route('admin.inquiries.estado', $this->lead), ['estado' => 'contactado', 'nota' => 'Le escribi por WhatsApp, quiere visitar el sabado.'])
            ->assertRedirect();

        $lead = $this->lead->fresh();
        $this->assertSame('contactado', $lead->estado);
        $this->assertSame('Le escribi por WhatsApp, quiere visitar el sabado.', $lead->nota);
        $this->assertSame($this->ana->id, $lead->atendido_por);
        $this->assertNotNull($lead->estado_en);
        $this->assertTrue($lead->read, 'atendido y sigue contando como no leido');
    }

    public function test_un_estado_inventado_no_entra(): void
    {
        $this->actingAs($this->ana)
            ->patch(route('admin.inquiries.estado', $this->lead), ['estado' => 'vendido-seguro'])
            ->assertSessionHasErrors('estado');

        $this->assertSame(Inquiry::NUEVO, $this->lead->fresh()->estado);
    }

    public function test_otra_promotora_no_toca_lo_que_no_es_suyo(): void
    {
        $this->actingAs($this->otra)
            ->patch(route('admin.inquiries.estado', $this->lead), ['estado' => 'descartado'])
            ->assertForbidden();

        $this->assertSame(Inquiry::NUEVO, $this->lead->fresh()->estado);
    }

    public function test_la_bandeja_filtra_por_estado_y_lo_enseña(): void
    {
        $this->lead->update(['estado' => 'visita']);
        Inquiry::create([
            'project_id' => $this->lead->project_id, 'name' => 'Otro Comprador',
            'email' => 'otro@ejemplo.invalid', 'message' => 'Hola', 'read' => false,
        ]);

        $this->actingAs($this->ana)->get(route('admin.inquiries.index', ['estado' => 'visita']))
            ->assertOk()
            ->assertSee('Comprador Interesado')
            ->assertDontSee('Otro Comprador');

        $this->actingAs($this->ana)->get(route('admin.inquiries.index', ['estado' => 'nuevo']))
            ->assertOk()
            ->assertSee('Otro Comprador')
            ->assertDontSee('Comprador Interesado');

        $this->actingAs($this->ana)->get(route('admin.inquiries.show', $this->lead))
            ->assertOk()
            ->assertSee(__('inquiry.estado_visita'))
            ->assertSee('name="nota"', false);
    }

    public function test_un_lead_sin_atender_un_dia_se_avisa_una_sola_vez(): void
    {
        $this->lead->forceFill(['created_at' => now()->subHours(30)])->save();

        $this->artisan('leads:sin-atender')->assertSuccessful();

        Mail::assertQueued(LeadsSinAtender::class, fn ($m) => $m->hasTo('ventas@ana.invalid') && $m->leads->count() === 1);
        $this->assertNotNull($this->lead->fresh()->avisado_sin_atender_en);

        // Al dia siguiente sigue sin atender, y no se vuelve a avisar: un
        // recordatorio que se repite se deja de leer.
        $this->travel(1)->days();
        $this->artisan('leads:sin-atender')->assertSuccessful();
        Mail::assertQueuedCount(1);
    }

    public function test_varios_leads_de_la_misma_promotora_van_en_un_solo_correo(): void
    {
        // Lo que dijo el revisor: la regla central era esta y ningun test la
        // miraba. Con el queue() dentro del bucle de leads todo seguia verde.
        $this->lead->forceFill(['created_at' => now()->subHours(30)])->save();
        Inquiry::create([
            'project_id' => $this->lead->project_id, 'name' => 'Segundo Comprador',
            'email' => 'segundo@ejemplo.invalid', 'message' => 'Hola', 'read' => false,
        ])->forceFill(['created_at' => now()->subHours(26)])->save();

        $this->artisan('leads:sin-atender')->assertSuccessful();

        Mail::assertQueuedCount(1);
        Mail::assertQueued(LeadsSinAtender::class, fn ($m) => $m->leads->count() === 2);
    }

    public function test_los_atendidos_y_los_recientes_no_se_avisan(): void
    {
        $this->lead->forceFill(['created_at' => now()->subHours(30), 'estado' => 'contactado'])->save();
        Inquiry::create([
            'project_id' => $this->lead->project_id, 'name' => 'Recien Llegado',
            'email' => 'reciente@ejemplo.invalid', 'message' => 'Hola', 'read' => false,
        ]);

        $this->artisan('leads:sin-atender')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_el_aviso_esta_en_la_agenda(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command ?? '', 'leads:sin-atender'));

        $this->assertNotNull($evento, 'nadie avisa de los leads sin atender');
    }
}
