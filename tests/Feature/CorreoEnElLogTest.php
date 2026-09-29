<?php

namespace Tests\Feature;

use App\Mail\NewInquiryNotification;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use App\Support\Salud\CorreoEnElLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Que la comprobacion nocturna lea de verdad el correo que se escribio.
 *
 * El gancho del correo decia "el aviso salio" mirando el destinatario y la
 * marca del aviso por separado en todo el fichero. Y el commit que lo trajo
 * decia "comprobado contra desarrollo" sin dejar nada que se pudiera repetir.
 * Esto es lo que faltaba: un correo escrito por el mailer de verdad, con la
 * plantilla de verdad, leido por el mismo codigo que usa el gancho.
 */
class CorreoEnElLogTest extends TestCase
{
    use RefreshDatabase;

    private string $fichero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fichero = storage_path('logs/correo-de-prueba.log');
        File::delete($this->fichero);

        config([
            'logging.channels.correo_de_prueba' => [
                'driver' => 'single',
                'path' => $this->fichero,
                'level' => 'debug',
            ],
            'mail.mailers.log.channel' => 'correo_de_prueba',
        ]);
    }

    protected function tearDown(): void
    {
        File::delete($this->fichero);
        parent::tearDown();
    }

    /** Dos mensajes tal como los escribe el mailer, para los casos que no se pueden mandar. */
    private function dosMensajes(): string
    {
        return "[2026-09-29 07:47:56] local.DEBUG: From: \"Real3D.io\" <noreply@real3d.io>\r\n"
            ."To: ana@promotora.invalid\r\n"
            ."Subject: Nueva consulta: Residencial Bahia - Comprador Extranjero\r\n\r\n"
            ."cuerpo uno\n"
            ."[2026-09-29 07:47:58] local.DEBUG: From: \"Real3D.io\" <noreply@real3d.io>\r\n"
            ."To: otra@parte.invalid\r\n"
            ."Subject: Nueva consulta: Residencial Bahia - Comprador Del Chat\r\n\r\n"
            ."cuerpo dos\n";
    }

    public function test_el_destinatario_y_la_marca_tienen_que_ir_en_el_mismo_mensaje(): void
    {
        $log = $this->dosMensajes();

        $this->assertTrue(CorreoEnElLog::hayAvisoPara($log, 'ana@promotora.invalid', 'Residencial Bahia - Comprador Extranjero'));

        // Lo que antes pasaba: la promotora esta en un mensaje y la marca del
        // chat en otro, dirigido a otra parte.
        $this->assertFalse(CorreoEnElLog::hayAvisoPara($log, 'ana@promotora.invalid', 'Residencial Bahia - Comprador Del Chat'),
            'dio por bueno un aviso cuyo destinatario y marca estan en mensajes distintos');

        $this->assertFalse(CorreoEnElLog::hayAvisoPara($log, 'nadie@promotora.invalid'));
        $this->assertTrue(CorreoEnElLog::hayAvisoPara($log, 'otra@parte.invalid'));
    }

    public function test_un_asunto_largo_partido_en_dos_lineas_se_sigue_encontrando(): void
    {
        // RFC 5322: una cabecera larga sigue en la linea siguiente con un
        // espacio delante. Pasa con nombres de proyecto largos.
        $log = "[2026-09-29 07:47:56] local.DEBUG: From: x@y.invalid\r\n"
            ."To: ana@promotora.invalid\r\n"
            ."Subject: Nueva consulta: Residencial Los Altos de la Bahia de\r\n"
            ." Samana Torre Norte - Comprador Extranjero\r\n\r\ncuerpo\n";

        $this->assertTrue(CorreoEnElLog::hayAvisoPara($log, 'ana@promotora.invalid',
            'Samana Torre Norte - Comprador Extranjero'));
    }

    public function test_el_aviso_de_verdad_escrito_por_el_mailer_de_verdad_se_lee(): void
    {
        // Lo que ninguna tira escrita a mano demuestra: que el formato con el
        // que Laravel escribe un correo al log, con la plantilla real del
        // aviso, es el que este codigo sabe leer. Y que la marca que usa el
        // recorrido nocturno -- nombre del proyecto, guion, nombre del
        // comprador -- es de verdad parte del asunto.
        $promotora = User::factory()->create(['role' => 'inmobiliaria']);
        $proyecto = Project::create([
            'name' => 'Recorrido automatico 123456',
            'slug' => 'recorrido-automatico-123456',
            'status' => 'public',
            'created_by' => $promotora->id,
        ]);
        $consulta = Inquiry::create([
            'project_id' => $proyecto->id,
            'name' => 'Comprador Del Chat',
            'email' => 'comprador@ejemplo.invalid',
            'message' => 'Me interesa.',
            'read' => false,
        ]);

        Mail::mailer('log')->to('ana@promotora.invalid')->send(new NewInquiryNotification($consulta));

        $this->assertFileExists($this->fichero, 'el mailer log no escribio nada en su canal');
        $log = file_get_contents($this->fichero);

        $this->assertTrue(CorreoEnElLog::hayAvisoPara($log, 'ana@promotora.invalid',
            'Recorrido automatico 123456 - Comprador Del Chat'),
            'el aviso real no se reconoce con la marca que usa el recorrido');

        $this->assertFalse(CorreoEnElLog::hayAvisoPara($log, 'ana@promotora.invalid',
            'Recorrido automatico 123456 - Comprador Extranjero'));
        $this->assertFalse(CorreoEnElLog::hayAvisoPara($log, 'otra@promotora.invalid',
            'Recorrido automatico 123456 - Comprador Del Chat'));
    }
}
