<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuanto queda por vender, enseñado al comprador.
 *
 * Quien compra a distancia y sin conocer a nadie busca señales de que no es el
 * primero. "19 disponibles" no dice nada; "19 de 61" dice a la vez que queda
 * poco y que sesenta personas ya se fiaron.
 *
 * El idioma va en la URL (?lang=), no con setLocale: el middleware SetLocale
 * corre en cada peticion y pisaria cualquier idioma fijado desde el test.
 */
class DisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $autor = User::factory()->create(['role' => 'superadmin']);
        $this->proyecto = Project::create([
            'name' => 'Residencial de prueba',
            'slug' => 'residencial-de-prueba',
            'status' => 'public',
            // El portal solo saca proyectos con ubicacion y coordenadas
            // (scopePortalVisible); sin ellas la tarjeta ni se pinta.
            'location' => 'Punta Cana',
            'latitude' => 18.5601,
            'longitude' => -68.3725,
            'created_by' => $autor->id,
        ]);
    }

    private function viviendas(int $disponibles, int $reservadas, int $vendidas): void
    {
        $n = 0;
        foreach ([['available', $disponibles], ['reserved', $reservadas], ['sold', $vendidas]] as [$estado, $cuantas]) {
            for ($i = 0; $i < $cuantas; $i++) {
                Unit::create([
                    'project_id' => $this->proyecto->id,
                    'identifier' => 'U-'.(++$n),
                    'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1,
                    'area_m2' => 80, 'price' => 150000,
                    'status' => $estado, 'sort_order' => $n,
                ]);
            }
        }
    }

    /** La ficha del proyecto en el idioma pedido. */
    private function ficha(string $idioma = 'es')
    {
        return $this->get(route('viewer.landing', $this->proyecto->slug).'?lang='.$idioma);
    }

    public function test_la_ficha_dice_cuantas_quedan_del_total(): void
    {
        $this->viviendas(disponibles: 19, reservadas: 3, vendidas: 39);

        $this->ficha()->assertOk()->assertSee('19 de 61');
    }

    public function test_enseña_el_porcentaje_ya_colocado(): void
    {
        // 61 en total, 19 libres: 42 colocadas, el 69%.
        $this->viviendas(disponibles: 19, reservadas: 3, vendidas: 39);

        $this->ficha()->assertSee('69%');
    }

    public function test_menciona_las_reservadas_cuando_las_hay(): void
    {
        $this->viviendas(disponibles: 10, reservadas: 2, vendidas: 5);

        $this->ficha()->assertSee('2 reservadas ahora mismo');
    }

    public function test_no_menciona_reservas_si_no_hay(): void
    {
        $this->viviendas(disponibles: 10, reservadas: 0, vendidas: 5);

        $this->ficha()->assertDontSee('reservadas ahora mismo');
    }

    public function test_un_proyecto_sin_viviendas_no_enseña_nada(): void
    {
        // Sin el guarda saldria un "0 de 0" y una barra vacia.
        $this->ficha()->assertOk()->assertDontSee('unidades disponibles');
    }

    public function test_todo_vendido_no_rompe_el_calculo(): void
    {
        $this->viviendas(disponibles: 0, reservadas: 0, vendidas: 20);

        $this->ficha()->assertOk()->assertSee('0 de 20');
    }

    public function test_tambien_funciona_en_ingles(): void
    {
        // Buena parte de los compradores son extranjeros: el ingles no es un extra.
        $this->viviendas(disponibles: 19, reservadas: 2, vendidas: 39);

        $this->ficha('en')
            ->assertOk()
            ->assertSee('19 of 60')
            ->assertSee('2 reserved right now');
    }

    public function test_la_tarjeta_del_portal_tambien_da_el_total(): void
    {
        $this->viviendas(disponibles: 19, reservadas: 0, vendidas: 42);

        $this->get(route('portal.home').'?lang=es')
            ->assertOk()
            ->assertSee('19 de 61');
    }
}
