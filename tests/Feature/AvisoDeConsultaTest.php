<?php

namespace Tests\Feature;

use App\Mail\InquiryAutoReply;
use App\Mail\NewInquiryNotification;
use App\Models\ChatbotConversation;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use App\Support\Leads\AvisoDeConsulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Que un lead avise, por donde sea que entre.
 *
 * Hay dos formas de dejar los datos: el formulario de la ficha y el chatbot. El
 * formulario avisaba y el chatbot no: guardaba la consulta en la base y no
 * mandaba nada, asi que la promotora tenia el lead delante y no lo sabia. En
 * produccion habia uno asi.
 *
 * Es el mismo fallo que ya costo semanas por el otro camino. Por eso los dos
 * usan ahora la misma clase, y por eso este fichero prueba los dos: arreglar un
 * camino y dejar el de al lado es lo que nos trajo hasta aqui.
 */
class AvisoDeConsultaTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create([
            'role' => User::ROLE_INMOBILIARIA,
            'email' => 'promotora@ejemplo.invalid',
        ]);

        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia-avisos',
            'status' => 'public',
            'created_by' => $this->promotora->id,
            'contact_email' => 'ventas@promotora.invalid',
        ]);

        $this->promotora->assignedProjects()->attach($this->proyecto->id);
    }

    public function test_el_formulario_de_la_ficha_avisa(): void
    {
        Mail::fake();

        $this->post(route('viewer.inquiry', $this->proyecto), [
            'name' => 'Comprador Uno',
            'email' => 'comprador.uno@ejemplo.invalid',
            'phone' => '+1 809 555 0100',
            'message' => 'Me interesa la A-102.',
        ]);

        Mail::assertQueued(NewInquiryNotification::class);
        Mail::assertQueued(InquiryAutoReply::class);
    }

    public function test_el_chatbot_tambien_avisa(): void
    {
        // Lo que no hacia. La consulta se guardaba y ahi se quedaba.
        Mail::fake();

        $conversacion = ChatbotConversation::create([
            'project_id' => $this->proyecto->id,
            'session_id' => 'sesion-de-prueba-0001',
            'locale' => 'es',
        ]);

        // La ruta no tiene nombre; se pide por su direccion, que es ademas
        // como la llama el propio chatbot desde el navegador. Esta enlaza por
        // slug -- las de /api/projects no son todas iguales, la de ficheros va
        // por id -- asi que con el id devuelve 404 y no prueba nada.
        $this->postJson('/api/projects/'.$this->proyecto->slug.'/chat/lead', [
            'session_id' => $conversacion->session_id,
            'name' => 'Comprador Dos',
            'email' => 'comprador.dos@ejemplo.invalid',
            'phone' => '+1 809 555 0200',
        ])->assertOk();

        $this->assertDatabaseHas('inquiries', ['email' => 'comprador.dos@ejemplo.invalid']);

        Mail::assertQueued(NewInquiryNotification::class);
        Mail::assertQueued(InquiryAutoReply::class);
    }

    public function test_los_dos_caminos_avisan_a_la_misma_gente(): void
    {
        // Si un camino avisara a otros, seria un lead perdido a medias: llega
        // a alguien pero no a quien vende.
        $consulta = Inquiry::create([
            'project_id' => $this->proyecto->id,
            'name' => 'Quien Sea',
            'email' => 'quien.sea@ejemplo.invalid',
            'message' => 'Hola',
            'read' => false,
        ]);

        $destinatarios = AvisoDeConsulta::destinatarios($this->proyecto);

        $this->assertContains('ventas@promotora.invalid', $destinatarios->all(),
            'el correo de contacto del proyecto no recibe el aviso');
        $this->assertNotEmpty($consulta->id);
    }

    public function test_sin_correo_de_contacto_avisa_a_la_promotora(): void
    {
        // El campo que mas se olvida al dar de alta un proyecto. Sin esto el
        // aviso se iba solo a los superadmin del SaaS.
        $this->proyecto->update(['contact_email' => null]);

        $destinatarios = AvisoDeConsulta::destinatarios($this->proyecto->fresh());

        $this->assertContains('promotora@ejemplo.invalid', $destinatarios->all());
    }
}
