<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * El numero de version que acaba publicado.
 *
 * Sale en /version, en la meta del HTML y en el pie del panel. Cuando se pierde
 * no falla nada: el despliegue termina bien, la web funciona, y solo el numero
 * deja de estar. Hoy llevaba un rato diciendo "sin-tag" sin que nadie lo notara.
 *
 * Se prueba el guion de verdad, el mismo que usa el despliegue, y no una copia
 * de su logica: una copia se queda atras y entonces el test da confianza sobre
 * algo que ya no existe.
 */
class LeerVersionTest extends TestCase
{
    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carpeta = sys_get_temp_dir().'/version-'.uniqid();
        mkdir($this->carpeta);
    }

    protected function tearDown(): void
    {
        @unlink($this->carpeta.'/version.txt');
        @rmdir($this->carpeta);

        parent::tearDown();
    }

    private function leer(?string $contenido): string
    {
        if ($contenido !== null) {
            file_put_contents($this->carpeta.'/version.txt', $contenido);
        }

        $guion = base_path('deploy/leer-version.sh');

        return trim(shell_exec(sprintf('bash %s %s 2>/dev/null',
            escapeshellarg($guion), escapeshellarg($this->carpeta))) ?? '');
    }

    public function test_lee_la_version_del_fichero(): void
    {
        $this->assertSame('v1.3.1', $this->leer('1.3.1'));
    }

    public function test_un_salto_de_linea_al_final_no_estorba(): void
    {
        // Es como lo deja release-please.
        $this->assertSame('v1.4.0', $this->leer("1.4.0\n"));
    }

    public function test_no_duplica_la_v_si_ya_viene(): void
    {
        $this->assertSame('v2.0.0', $this->leer('v2.0.0'));
    }

    public function test_un_fichero_guardado_desde_windows_no_mete_basura(): void
    {
        // Con CRLF quedaba un \r pegado al numero, y como la comprobacion no
        // anclaba el final habria colado una version con un caracter de control
        // dentro: valida a la vista, rota en cualquier sitio que la use.
        $this->assertSame('v1.5.2', $this->leer("1.5.2\r\n"));
    }

    // --- Lo que no debe pasar por bueno -----------------------------------

    public function test_un_fichero_vacio_no_da_una_version(): void
    {
        // Este era el fallo: `[ -r fichero ]` pasaba y el resultado era "v".
        $this->assertNotSame('v', $this->leer(''));
        $this->assertSame('sin-tag', $this->leer(''));
    }

    public function test_un_contenido_que_no_es_una_version_no_cuela(): void
    {
        $this->assertSame('sin-tag', $this->leer('en construccion'));
        $this->assertSame('sin-tag', $this->leer('1.3'));
        $this->assertSame('sin-tag', $this->leer('v'));
    }

    public function test_sin_fichero_y_sin_git_lo_dice(): void
    {
        $this->assertSame('sin-tag', $this->leer(null));
    }

    public function test_sin_fichero_tira_de_la_etiqueta_de_git(): void
    {
        // El respaldo tambien es codigo: si alguien le cambia los flags a
        // `git describe`, nadie se enteraria. Es la rama que fallaba con clones
        // superficiales, asi que conviene tenerla sujeta.
        shell_exec(sprintf(
            'cd %s && git init -q && git config user.email t@t.t && git config user.name t '
            .'&& git commit -q --allow-empty -m inicial && git tag v9.9.9 2>/dev/null',
            escapeshellarg($this->carpeta)
        ));

        $this->assertSame('v9.9.9', $this->leer(null));

        shell_exec('rm -rf '.escapeshellarg($this->carpeta.'/.git'));
    }
}
