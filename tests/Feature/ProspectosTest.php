<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Las comprobaciones de tools/prospectos.py, dentro de la suite de siempre.
 *
 * El guion es Python y el CI corre pint y php artisan test. En vez de anadirle
 * un paso al workflow -- que hay que acordarse de mantener y que nadie mira
 * hasta que falla -- se hace como con deploy/leer-version.sh: un test de aqui
 * lo ejecuta. Si sus comprobaciones se rompen, se rompe el CI.
 *
 * Y no se salta si no hay python3. Un test que se salta solo es un test que
 * pasa siempre, que es justo lo que llevamos todo el dia quitando de en medio:
 * si el interprete no esta, el CI tiene que decirlo, no callarse.
 */
class ProspectosTest extends TestCase
{
    public function test_las_comprobaciones_del_recolector_pasan(): void
    {
        $guion = base_path('tools/probar-prospectos.py');
        $this->assertFileExists($guion);

        $salida = [];
        $codigo = 0;
        exec(escapeshellarg($this->interprete()).' '.escapeshellarg($guion).' 2>&1', $salida, $codigo);

        $this->assertSame(0, $codigo,
            "tools/probar-prospectos.py fallo:\n".implode("\n", $salida));
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
