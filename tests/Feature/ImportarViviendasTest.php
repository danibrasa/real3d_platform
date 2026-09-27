<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitTypology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * El recorrido completo del importador, que es lo que decide si una promotora
 * puede poner su proyecto en marcha sola o necesita que se lo hagamos nosotros.
 */
class ImportarViviendasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'superadmin']);
        $this->proyecto = Project::create([
            'name' => 'Proyecto de prueba',
            'slug' => 'proyecto-de-prueba',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);
    }

    private function csv(string $contenido, string $nombre = 'viviendas.csv'): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($ruta, $contenido);

        return new UploadedFile($ruta, $nombre, 'text/csv', null, true);
    }

    // --- El recorrido completo -------------------------------------------

    public function test_una_promotora_carga_su_listado_de_una_vez(): void
    {
        $fichero = $this->csv(<<<'CSV'
        Código;Nº Planta;Dormitorios;Baños;Superficie (m²);Precio de venta (€);Situación
        A-101;1;2;2;85,50;185.000 €;Disponible
        A-102;1;3;2;102,30;214.500 €;Reservado
        B-201;2;2;1;78,00;168.900 €;Vendido
        CSV);

        // Paso 1: sube el fichero y ve lo que el sistema ha entendido.
        $revision = $this->actingAs($this->admin)->post(
            route('admin.projects.units.import.analizar', $this->proyecto),
            ['fichero' => $fichero]
        );

        $revision->assertOk();
        $revision->assertViewHas('total', 3);
        $revision->assertSee('A-101');
        $revision->assertSee('185.000'); // el precio ya interpretado

        $mapeo = $revision->viewData('mapeo');
        $this->assertSame(0, $mapeo['identifier'], 'no ha reconocido el identificador');
        $this->assertSame(5, $mapeo['price'], 'no ha reconocido el precio');

        // Paso 2: confirma.
        $this->actingAs($this->admin)
            ->post(route('admin.projects.units.import.confirmar', $this->proyecto), [
                'mapeo' => $mapeo,
                'duplicados' => 'saltar',
            ])
            ->assertRedirect(route('admin.projects.units.index', $this->proyecto));

        $this->assertSame(3, $this->proyecto->units()->count());

        $a101 = Unit::where('identifier', 'A-101')->first();
        $this->assertSame(1, $a101->floor);
        $this->assertSame(2, $a101->bedrooms);
        $this->assertEquals(85.5, $a101->area_m2);
        $this->assertEquals(185000, $a101->price);
        $this->assertSame('available', $a101->status);

        $this->assertSame('reserved', Unit::where('identifier', 'A-102')->first()->status);
        $this->assertSame('sold', Unit::where('identifier', 'B-201')->first()->status);
    }

    public function test_crea_las_tipologias_que_no_existan(): void
    {
        $fichero = $this->csv(<<<'CSV'
        Unidad;Tipo;Dormitorios;Precio
        A-01;Tipo A;2;180000
        A-02;Tipo A;2;185000
        B-01;Tipo B;3;240000
        CSV);

        $this->importar($fichero);

        // Dos tipologias, no tres: "Tipo A" aparece dos veces.
        $this->assertSame(2, UnitTypology::where('project_id', $this->proyecto->id)->count());
        $this->assertNotNull(
            Unit::where('identifier', 'A-01')->first()->typology_id,
            'la vivienda ha quedado sin tipologia'
        );
    }

    // --- Que pasa con lo que ya existe ------------------------------------

    public function test_por_defecto_no_toca_las_viviendas_que_ya_estan(): void
    {
        $this->viviendaExistente('A-101', 100000);

        $this->importar($this->csv("Unidad;Precio\nA-101;999999\nA-102;150000"));

        $this->assertEquals(100000, Unit::where('identifier', 'A-101')->first()->price,
            'ha pisado una vivienda existente sin que se lo pidieran');
        $this->assertSame(2, $this->proyecto->units()->count());
    }

    public function test_actualiza_si_se_le_pide(): void
    {
        $this->viviendaExistente('A-101', 100000);

        $this->importar($this->csv("Unidad;Precio\nA-101;150000"), 'actualizar');

        $this->assertEquals(150000, Unit::where('identifier', 'A-101')->first()->price);
        $this->assertSame(1, $this->proyecto->units()->count(), 'ha duplicado la vivienda');
    }

    // --- Cuando el fichero viene mal --------------------------------------

    public function test_una_fila_sin_identificador_no_tumba_el_resto(): void
    {
        // Es preferible cargar las buenas y enseñar los fallos, que rechazarlo todo.
        $this->importar($this->csv("Unidad;Precio\nA-01;100000\n;200000\nA-03;300000"));

        $this->assertSame(2, $this->proyecto->units()->count());
    }

    public function test_rechaza_un_fichero_que_no_se_puede_leer(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.projects.units.import.analizar', $this->proyecto), [
                'fichero' => UploadedFile::fake()->create('cosas.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('fichero');

        $this->assertSame(0, $this->proyecto->units()->count());
    }

    public function test_rechaza_un_fichero_sin_filas(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.projects.units.import.analizar', $this->proyecto), [
                'fichero' => $this->csv('Unidad;Precio'),
            ])
            ->assertSessionHasErrors('fichero');
    }

    public function test_exige_saber_cual_es_el_identificador(): void
    {
        $this->actingAs($this->admin)->post(
            route('admin.projects.units.import.analizar', $this->proyecto),
            ['fichero' => $this->csv("Unidad;Precio\nA-01;100000")]
        );

        $this->actingAs($this->admin)
            ->post(route('admin.projects.units.import.confirmar', $this->proyecto), [
                'mapeo' => ['identifier' => null, 'price' => 1],
                'duplicados' => 'saltar',
            ])
            ->assertSessionHasErrors('mapeo');

        $this->assertSame(0, $this->proyecto->units()->count());
    }

    public function test_sin_fichero_en_sesion_manda_a_empezar_de_nuevo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.projects.units.import.confirmar', $this->proyecto), [
                'mapeo' => ['identifier' => 0],
                'duplicados' => 'saltar',
            ])
            ->assertRedirect(route('admin.projects.units.import.create', $this->proyecto));
    }

    // --- Quien puede importar ---------------------------------------------

    public function test_un_visitante_no_puede_importar(): void
    {
        $this->get(route('admin.projects.units.import.create', $this->proyecto))
            ->assertRedirect(route('login'));
    }

    public function test_un_usuario_normal_no_puede_importar(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.projects.units.import.create', $this->proyecto))
            ->assertForbidden();
    }

    // --- Utilidades --------------------------------------------------------

    private function importar(UploadedFile $fichero, string $duplicados = 'saltar'): void
    {
        $revision = $this->actingAs($this->admin)->post(
            route('admin.projects.units.import.analizar', $this->proyecto),
            ['fichero' => $fichero]
        );

        $this->actingAs($this->admin)->post(
            route('admin.projects.units.import.confirmar', $this->proyecto),
            ['mapeo' => $revision->viewData('mapeo'), 'duplicados' => $duplicados]
        );
    }

    private function viviendaExistente(string $identificador, float $precio): Unit
    {
        return Unit::create([
            'project_id' => $this->proyecto->id,
            'identifier' => $identificador,
            'floor' => 1,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'area_m2' => 80,
            'price' => $precio,
            'status' => 'available',
            'sort_order' => 1,
        ]);
    }

    // --- Que las pantallas se pinten --------------------------------------

    public function test_la_pantalla_de_subir_se_pinta(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.projects.units.import.create', $this->proyecto))
            ->assertOk()
            ->assertSee('Sube tu listado')
            ->assertSee('Excel');
    }

    public function test_la_pantalla_de_revisar_enseña_el_emparejamiento_y_la_vista_previa(): void
    {
        $respuesta = $this->actingAs($this->admin)->post(
            route('admin.projects.units.import.analizar', $this->proyecto),
            ['fichero' => $this->csv(<<<'CSV'
            Código;Planta;Precio;Tipo
            A-101;1;185.000 €;Tipo A
            CSV)]
        );

        $respuesta->assertOk()
            ->assertSee('Qué es cada columna')
            ->assertSee('Así quedarían', false)
            ->assertSee('A-101')          // la fila interpretada
            ->assertSee('185.000')        // el precio ya limpio
            ->assertSee('Tipo A');        // la tipologia detectada
    }

    public function test_avisa_de_las_viviendas_que_ya_existen(): void
    {
        $this->viviendaExistente('A-101', 100000);

        $this->actingAs($this->admin)
            ->post(route('admin.projects.units.import.analizar', $this->proyecto),
                ['fichero' => $this->csv("Unidad;Precio\nA-101;150000")])
            ->assertOk()
            ->assertSee('ya existe');
    }

    public function test_el_listado_ofrece_importar_y_resume_el_resultado(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.projects.units.index', $this->proyecto))
            ->assertOk()
            ->assertSee(route('admin.projects.units.import.create', $this->proyecto), false);

        $this->importar($this->csv("Unidad;Precio\nA-01;100000\n;200000"));

        $this->actingAs($this->admin)
            ->get(route('admin.projects.units.index', $this->proyecto))
            ->assertOk()
            ->assertSee('Importación terminada', false);
    }
}
