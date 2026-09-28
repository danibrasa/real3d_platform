<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Una vivienda sin precio no vale cero.
 *
 * La columna era NOT NULL, asi que "aun no tiene precio" y "vale 0" eran lo
 * mismo, y en la web salia publicado un "0". Un hueco se entiende; un cero
 * parece una oferta. Salio importando folletos, donde una unidad que pone
 * "Consultar" es lo mas normal del mundo.
 */
class PrecioSinPonerTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $promotora = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora',
            'slug' => 'promotora',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);

        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'latitude' => 18.58,
            'longitude' => -68.40,
            'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($this->proyecto->id);

        ProjectFile::create([
            'project_id' => $this->proyecto->id,
            'file_type' => 'image_360',
            'original_name' => 'fondo.png',
            'storage_path' => 'x/fondo.png',
            'mime_type' => 'image/png',
            'file_size' => 100,
            'upload_complete' => true,
        ]);
    }

    private function vivienda(?float $precio, string $id = 'A-101'): Unit
    {
        return Unit::create([
            'project_id' => $this->proyecto->id, 'identifier' => $id,
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area_m2' => 85.5,
            'price' => $precio, 'status' => 'available', 'sort_order' => 1,
        ]);
    }

    public function test_se_puede_guardar_una_vivienda_sin_precio(): void
    {
        $u = $this->vivienda(null);

        $this->assertNull($u->fresh()->price);
    }

    public function test_sin_precio_no_se_imprime_un_cero(): void
    {
        // Es el fallo entero en una linea: number_format(null) daba "0".
        $this->assertSame(
            __('general.price_on_request'),
            $this->vivienda(null)->formatted_price
        );
    }

    public function test_con_precio_se_imprime_el_precio(): void
    {
        $this->assertStringContainsString('185,000', $this->vivienda(185000)->formatted_price);
    }

    public function test_la_ficha_publica_dice_consultar_en_vez_de_cero(): void
    {
        $u = $this->vivienda(null);

        $this->get(route('viewer.unit.detail', [$this->proyecto->slug, $u->id]))
            ->assertOk()
            ->assertSee(__('general.price_on_request'))
            ->assertDontSee('USD 0');
    }

    public function test_el_precio_por_metro_no_se_calcula_sin_precio(): void
    {
        // Sin esto salia "0 por m2", que es una afirmacion falsa sobre el piso.
        $u = $this->vivienda(null);

        $this->get(route('viewer.unit.detail', [$this->proyecto->slug, $u->id]))
            ->assertOk()
            ->assertDontSee(__('unit_detail.price_per_m2'));
    }

    public function test_lo_que_queda_por_pagar_no_se_inventa(): void
    {
        // Devolver 0 haria creer que la vivienda esta pagada del todo.
        $this->assertSame(0.0, $this->vivienda(null)->pendingAmount());
    }

    public function test_el_rango_de_precios_ignora_las_que_no_tienen(): void
    {
        $this->vivienda(null, 'A-101');
        $this->vivienda(185000, 'A-102');
        $this->vivienda(225000, 'A-103');

        // MIN() y MAX() de SQL ya ignoran los nulos, asi que el rango sale de
        // las que si tienen precio. Queda como guardia por si alguien lo
        // reescribe en PHP y pierde esa propiedad.
        $rango = $this->proyecto->fresh()->price_range;

        $this->assertStringContainsString('185,000', $rango);
        $this->assertStringContainsString('225,000', $rango);
        $this->assertStringNotContainsString('USD 0', $rango);
    }

    public function test_el_importador_deja_el_hueco_en_vez_de_poner_cero(): void
    {
        $promotora = $this->proyecto->assignedAgencies()->first();

        $csv = "Unidad;Precio\nA-201;\nA-202;185000\n";
        $fichero = UploadedFile::fake()->createWithContent('viviendas.csv', $csv);

        $this->actingAs($promotora)
            ->post(route('admin.projects.units.import.analizar', $this->proyecto), ['fichero' => $fichero])
            ->assertOk();

        $this->actingAs($promotora)
            ->post(route('admin.projects.units.import.confirmar', $this->proyecto), [
                'mapeo' => ['identifier' => 0, 'price' => 1],
                'duplicados' => 'saltar',
            ]);

        $this->assertNull(Unit::where('identifier', 'A-201')->first()?->price);
        $this->assertSame(185000.0, (float) Unit::where('identifier', 'A-202')->first()?->price);
    }
}
