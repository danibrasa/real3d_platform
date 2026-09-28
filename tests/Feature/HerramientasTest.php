<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Las comprobaciones de tools/, dentro de la suite de siempre.
 *
 * Los guiones de tools/ son Python y el CI corre pint y php artisan test. En
 * vez de anadirle pasos al workflow -- que hay que acordarse de mantener y que
 * nadie mira hasta que falla -- se hace como con deploy/leer-version.sh: un
 * test de aqui los ejecuta. Si sus comprobaciones se rompen, se rompe el CI.
 *
 * Se descubren solos: cualquier tools/probar-*.php nuevo entra sin tocar esto.
 * Antes iban uno a uno y el segundo se quedo fuera del CI sin que nada lo
 * dijera, que es como se pierden las redes de seguridad.
 *
 * Y no se salta si no hay python3. Un test que se salta solo es un test que
 * pasa siempre, que es justo lo que llevamos quitando de en medio: si el
 * interprete no esta, el CI tiene que decirlo, no callarse.
 */
class HerramientasTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function guiones(): array
    {
        $encontrados = glob(dirname(__DIR__, 2).'/tools/probar-*.py') ?: [];

        $casos = [];
        foreach ($encontrados as $ruta) {
            $casos[basename($ruta)] = [$ruta];
        }

        return $casos;
    }

    /**
     * @dataProvider guiones
     */
    public function test_las_comprobaciones_de_la_herramienta_pasan(string $guion): void
    {
        $salida = [];
        $codigo = 0;
        exec(escapeshellarg($this->interprete()).' '.escapeshellarg($guion).' 2>&1', $salida, $codigo);

        $this->assertSame(0, $codigo,
            basename($guion)." fallo:\n".implode("\n", $salida));
    }

    public function test_hay_comprobaciones_que_ejecutar(): void
    {
        // Sin esto, borrar todos los guiones dejaria el dataProvider vacio y
        // este fichero pasaria en verde sin ejecutar nada.
        $this->assertNotEmpty(self::guiones(), 'no se encontro ningun tools/probar-*.py');
    }

    /**
     * El interprete de Python, o el test falla diciendo que no hay.
     */
    private function interprete(): string
    {
        foreach (['python3', 'python'] as $candidato) {
            $donde = [];
            $codigo = 0;
            exec(escapeshellarg($candidato).' --version 2>&1', $donde, $codigo);

            if ($codigo === 0) {
                return $candidato;
            }
        }

        $this->fail('no hay python3 ni python: las comprobaciones de tools/ no se pueden ejecutar');
    }
}
