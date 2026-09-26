<?php

namespace Tests\Feature;

use App\Support\Version;
use Tests\TestCase;

class VersionTest extends TestCase
{
    public function test_la_ruta_version_devuelve_los_datos_del_despliegue(): void
    {
        $this->get('/version')
            ->assertOk()
            ->assertJsonStructure(['release', 'commit', 'commit_short', 'deployed_at', 'environment']);
    }

    public function test_sin_version_json_no_falla_y_cae_a_valores_de_desarrollo(): void
    {
        // En los tests no hay version.json, que es el caso del entorno local:
        // debe responder igualmente en vez de lanzar una excepcion.
        $v = Version::all();

        $this->assertArrayHasKey('release', $v);
        $this->assertNotSame('', $v['release']);
        $this->assertSame('testing', $v['environment']);
    }

    public function test_la_etiqueta_no_queda_vacia(): void
    {
        $this->assertNotSame('', trim(Version::label()));
    }

    public function test_la_franja_de_entorno_no_aparece_en_produccion(): void
    {
        // Es lo unico que separa staging de la web real a simple vista, asi que
        // conviene que no se cuele al reves: visible fuera de produccion, nunca dentro.
        $this->app['env'] = 'production';
        $this->assertStringNotContainsString('no es la web real', $this->renderBanner());

        $this->app['env'] = 'staging';
        $this->assertStringContainsString('no es la web real', $this->renderBanner());
    }

    private function renderBanner(): string
    {
        return view('components.env-banner')->render();
    }
}
