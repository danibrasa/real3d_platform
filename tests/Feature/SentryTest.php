<?php

namespace Tests\Feature;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SentryTest extends TestCase
{
    // La portada consulta proyectos: sin esquema, el test falla por la base de
    // datos y no por Sentry, que es lo que se quiere comprobar.
    use RefreshDatabase;

    public function test_la_app_funciona_sin_dsn_configurado(): void
    {
        // Sin DSN, Sentry queda desactivado. Lo que no puede pasar es que la
        // aplicacion deje de funcionar por ello: en local y en los tests no hay.
        config(['sentry.dsn' => null]);

        $this->get('/')->assertOk();
    }

    /**
     * Estas excepciones son ruido normal de cualquier web (paginas que no
     * existen, formularios mal rellenados, sesiones caducadas). Si llegasen a
     * Sentry agotarian la cuota y taparian los fallos de verdad.
     *
     * @dataProvider excepcionesQueNoSeAvisan
     */
    public function test_el_ruido_normal_no_llega_a_sentry(string $excepcion): void
    {
        $handler = app(ExceptionHandler::class);

        $this->assertFalse(
            $handler->shouldReport(new $excepcion('prueba')),
            "{$excepcion} no deberia avisarse: es ruido normal, no un fallo."
        );
    }

    public static function excepcionesQueNoSeAvisan(): array
    {
        return [
            'pagina no encontrada' => [NotFoundHttpException::class],
            'sesion caducada' => [TokenMismatchException::class],
        ];
    }

    public function test_los_errores_de_verdad_si_se_avisan(): void
    {
        // El contrapunto del test anterior: filtrar ruido no puede acabar
        // silenciando tambien los fallos reales.
        $handler = app(ExceptionHandler::class);

        $this->assertTrue($handler->shouldReport(new \RuntimeException('fallo real')));
        $this->assertTrue($handler->shouldReport(new \ErrorException('fallo real')));
    }

    public function test_la_validacion_no_se_avisa(): void
    {
        $handler = app(ExceptionHandler::class);

        $this->assertFalse($handler->shouldReport(
            ValidationException::withMessages(['campo' => 'obligatorio'])
        ));
        $this->assertFalse($handler->shouldReport(new AuthenticationException));
    }
}
