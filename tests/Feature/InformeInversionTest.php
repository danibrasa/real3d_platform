<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El informe de inversion en PDF, que es lo que el comprador reenvia.
 */
class InformeInversionTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    private Unit $vivienda;

    protected function setUp(): void
    {
        parent::setUp();

        // Esto queda fuera del PMV y para la promotora no existe; aqui se
        // enciende para probar la funcion, que sigue ahi para cuando vuelva.
        config(['pmv.activo' => false]);

        $autor = User::factory()->create(['role' => 'superadmin']);

        $this->proyecto = Project::create([
            'name' => 'Torre de prueba',
            'slug' => 'torre-de-prueba',
            'status' => 'public',
            'location' => 'Punta Cana',
            'latitude' => 18.56,
            'longitude' => -68.37,
            'created_by' => $autor->id,
            // Los supuestos de inversion que declara la promotora
            'avg_nightly_rate' => 185,
            'average_occupancy' => 78,
            'management_fee' => 20,
            'property_tax_rate' => 1,
            'appreciation_rate_annual' => 12,
        ]);

        $this->vivienda = Unit::create([
            'project_id' => $this->proyecto->id,
            'identifier' => 'A-101',
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2,
            'area_m2' => 85, 'price' => 185000,
            'status' => 'available', 'sort_order' => 1,
        ]);
    }

    private function url(?Project $p = null, ?Unit $u = null): string
    {
        return route('viewer.investment.pdf', [
            ($p ?? $this->proyecto)->slug,
            ($u ?? $this->vivienda)->id,
        ]);
    }

    public function test_cualquiera_puede_descargar_el_informe(): void
    {
        // Es material de venta: no requiere cuenta, como los otros PDFs.
        $r = $this->get($this->url());

        $r->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('content-type'));
        $this->assertStringContainsString('inversion-torre-de-prueba-A-101.pdf',
            $r->headers->get('content-disposition'));
    }

    public function test_el_pdf_tiene_contenido(): void
    {
        $contenido = $this->get($this->url())->getContent();

        $this->assertStringStartsWith('%PDF', $contenido);
        $this->assertGreaterThan(5000, strlen($contenido), 'el PDF sale casi vacio');
    }

    public function test_sin_datos_de_alquiler_no_se_ofrece(): void
    {
        // Un informe lleno de ceros seria peor que no ofrecerlo.
        $this->proyecto->update(['avg_nightly_rate' => null, 'average_occupancy' => null]);

        $this->get($this->url())->assertNotFound();
    }

    public function test_un_proyecto_en_borrador_no_expone_sus_numeros(): void
    {
        $this->proyecto->update(['status' => 'draft']);

        $this->get($this->url())->assertNotFound();
    }

    public function test_no_se_puede_pedir_una_vivienda_de_otro_proyecto(): void
    {
        $otro = Project::create([
            'name' => 'Otro', 'slug' => 'otro', 'status' => 'public',
            'created_by' => $this->proyecto->created_by,
            'avg_nightly_rate' => 100, 'average_occupancy' => 50,
        ]);

        $this->get($this->url($otro))->assertNotFound();
    }

    public function test_el_boton_aparece_en_la_ficha_cuando_hay_datos(): void
    {
        $this->get(route('viewer.unit.detail', [$this->proyecto->slug, $this->vivienda->id]).'?lang=es')
            ->assertOk()
            ->assertSee($this->url(), false);
    }

    public function test_el_boton_no_aparece_si_no_hay_datos(): void
    {
        $this->proyecto->update(['avg_nightly_rate' => null]);

        $this->get(route('viewer.unit.detail', [$this->proyecto->slug, $this->vivienda->id]).'?lang=es')
            ->assertOk()
            ->assertDontSee($this->url(), false);
    }
}
