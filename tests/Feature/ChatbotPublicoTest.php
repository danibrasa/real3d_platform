<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lo que el chatbot publico le cuesta a esta casa, y hasta donde.
 *
 * Cada respuesta es una llamada al proveedor de IA que pagamos nosotros, y la
 * API la puede llamar cualquiera con un guion. El tope de conversaciones por
 * direccion y hora estaba en la configuracion desde el primer dia y no lo
 * miraba nadie: con el limitador de diez mensajes por minuto, una sola
 * direccion podia abrir seiscientas conversaciones a la hora.
 */
class ChatbotPublicoTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        config(['chatbot.enabled' => true, 'chatbot.provider' => 'anthropic']);
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['text' => 'Claro, dime.']]]),
        ]);

        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        $limites = CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_PROFESSIONAL];
        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia-'.$promotora->id,
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => $limites['max_projects'],
            'max_storage_bytes' => $limites['max_storage_bytes'],
        ]);

        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($this->proyecto->id);
    }

    private function escribir(string $sesion, string $texto = 'Hola')
    {
        return $this->postJson('/api/projects/'.$this->proyecto->slug.'/chat', [
            'message' => $texto,
            'session_id' => $sesion,
        ]);
    }

    public function test_una_misma_direccion_no_abre_conversaciones_sin_fin(): void
    {
        $tope = (int) config('chatbot.max_conversations_per_ip_hour');
        $this->assertGreaterThan(0, $tope, 'sin tope configurado este test no mide nada');

        for ($i = 0; $i < $tope; $i++) {
            $this->escribir("sesion-000{$i}")->assertOk();
        }

        // Una mas, y se para. Sin gastar la llamada.
        $this->escribir('sesion-una-mas')->assertStatus(429);
        Http::assertSentCount($tope);

        // Seguir una conversacion que ya tenia abierta si: el tope es de
        // conversaciones nuevas, no de mensajes, que ya tienen el suyo.
        $this->escribir('sesion-0000', 'Y cuanto cuesta?')->assertOk();

        // Y desde otra direccion, tambien.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->escribir('sesion-de-otra-casa')
            ->assertOk();
    }

    public function test_las_conversaciones_de_hace_horas_ya_no_cuentan(): void
    {
        // El tope es por hora: una direccion que hablo ayer no esta castigada
        // hoy. Sin esto, la oficina de una promotora se quedaria sin chatbot
        // en su propia web al cabo de una semana.
        $tope = (int) config('chatbot.max_conversations_per_ip_hour');
        for ($i = 0; $i < $tope; $i++) {
            // created_at no es asignable en masa: se pone despues, a proposito.
            ChatbotConversation::create([
                'project_id' => $this->proyecto->id,
                'session_id' => "sesion-vieja-000{$i}",
                'ip_address' => '127.0.0.1',
            ])->forceFill(['created_at' => now()->subHours(2)])->save();
        }

        $this->escribir('sesion-de-hoy')->assertOk();
    }
}
