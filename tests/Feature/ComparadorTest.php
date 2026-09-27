<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comparar viviendas como las compara un inversor.
 *
 * El comparador enseñaba tipologia, planta, dormitorios, baños y superficie:
 * lo que mira quien busca casa. Aqui se vende a gente que pone tres unidades al
 * lado para ver cual renta mas por lo que cuesta, y de 47 visitantes que
 * abrieron una vivienda solo 2 llegaron a usarlo.
 */
class ComparadorTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $autor = User::factory()->create(['role' => 'superadmin']);

        $this->proyecto = Project::create([
            'name' => 'Torre Caribe',
            'slug' => 'torre-caribe',
            'status' => 'public',
            'location' => 'Punta Cana',
            'created_by' => $autor->id,
            'avg_nightly_rate' => 185,
            'average_occupancy' => 78,
            'management_fee' => 20,
            'property_tax_rate' => 1,
            'appreciation_rate_annual' => 12,
        ]);
    }

    private function vivienda(string $id, float $precio): Unit
    {
        return Unit::create([
            'project_id' => $this->proyecto->id,
            'identifier' => $id,
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2,
            'area_m2' => 85, 'price' => $precio,
            'status' => 'available', 'sort_order' => 1,
        ]);
    }

    public function test_cada_vivienda_lleva_sus_numeros_de_inversion(): void
    {
        $this->vivienda('A-101', 185000);

        $this->get(route('viewer.landing', $this->proyecto->slug))
            ->assertOk()
            ->assertSee('data-unit-yield', false)
            ->assertSee('data-unit-monthly', false)
            ->assertSee('data-unit-payback', false);
    }

    public function test_la_rentabilidad_baja_cuando_sube_el_precio(): void
    {
        // El mismo alquiler sobre mas dinero renta menos: es justo lo que el
        // comparador tiene que dejar ver de un vistazo.
        $barata = $this->vivienda('A-101', 150000);
        $cara = $this->vivienda('A-102', 300000);

        $this->assertGreaterThan(
            (float) $cara->investment_yield,
            (float) $barata->investment_yield,
            'la mas barata deberia rentar mas'
        );
    }

    public function test_el_retorno_es_mas_largo_en_la_mas_cara(): void
    {
        $barata = $this->vivienda('A-101', 150000);
        $cara = $this->vivienda('A-102', 300000);

        $this->assertLessThan(
            (float) $cara->investment_payback,
            (float) $barata->investment_payback,
            'la mas barata deberia recuperarse antes'
        );
    }

    public function test_sin_datos_de_alquiler_los_atributos_van_vacios(): void
    {
        // Vacios, no a cero: un cero se compara y gana o pierde, y estaria
        // comparando algo que nadie ha declarado.
        $this->proyecto->update(['avg_nightly_rate' => null, 'average_occupancy' => null]);
        $unidad = $this->vivienda('A-101', 185000);

        $this->assertSame('', $unidad->investment_yield);
        $this->assertSame('', $unidad->investment_monthly);
        $this->assertSame('', $unidad->investment_payback);
    }

    public function test_una_vivienda_que_no_se_recupera_deja_el_retorno_vacio(): void
    {
        // Impuestos altos y poco alquiler: el neto sale negativo.
        $this->proyecto->update([
            'avg_nightly_rate' => 40,
            'average_occupancy' => 20,
            'property_tax_rate' => 5,
        ]);
        $unidad = $this->vivienda('A-101', 500000);

        $this->assertSame('', $unidad->investment_payback);
    }

    public function test_el_comparador_ofrece_las_metricas_de_inversion(): void
    {
        $this->vivienda('A-101', 185000);

        $this->get(route('viewer.landing', $this->proyecto->slug).'?lang=es')
            ->assertOk()
            ->assertSee('Rentabilidad')
            ->assertSee('Neto al mes')
            ->assertSee('Se recupera en');
    }

    public function test_el_comparador_tambien_en_ingles(): void
    {
        $this->vivienda('A-101', 185000);

        $this->get(route('viewer.landing', $this->proyecto->slug).'?lang=en')
            ->assertOk()
            ->assertSee('Annual return')
            ->assertSee('Net per month');
    }
}
