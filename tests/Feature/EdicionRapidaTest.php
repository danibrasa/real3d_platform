<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Precio y estado editables en la tabla de viviendas: una promotora con
 * sesenta viviendas cambia lo que toca y guarda una vez, y cada fila que
 * falle se explica sin frenar a las demas.
 */
class EdicionRapidaTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private Project $proyecto;

    private Unit $a101;

    private Unit $a102;

    private Unit $b201;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        $this->empresa($this->promotora, 'promotora-de-prueba');
        $this->proyecto = Project::create(['name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'draft', 'created_by' => $this->promotora->id]);
        $this->promotora->assignedProjects()->attach($this->proyecto->id);
        $this->a101 = $this->vivienda('A-101', 185000);
        $this->a102 = $this->vivienda('A-102', 214500);
        $this->b201 = $this->vivienda('B-201', null);
    }

    /** Sin ficha de empresa, el panel manda a la promotora a rellenarla. */
    private function empresa(User $usuario, string $slug): void
    {
        CompanyProfile::create(['user_id' => $usuario->id, 'company_name' => $slug, 'slug' => $slug, 'plan_tier' => CompanyProfile::PLAN_STARTER, 'max_projects' => 5]);
    }

    private function vivienda(string $id, ?float $precio, string $estado = 'available'): Unit
    {
        return Unit::create(['project_id' => $this->proyecto->id, 'identifier' => $id, 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 80, 'price' => $precio, 'status' => $estado]);
    }

    private function guardar(array $datos, ?User $quien = null)
    {
        return $this->actingAs($quien ?? $this->promotora)->patch(route('admin.projects.units.lote', $this->proyecto), $datos);
    }

    public function test_la_tabla_lleva_precio_y_estado_editables_y_el_boton_de_guardar(): void
    {
        $this->actingAs($this->promotora)->get(route('admin.projects.units.index', $this->proyecto))
            ->assertOk()
            ->assertSee('name="v['.$this->a101->id.'][price]"', false)
            ->assertSee('name="v['.$this->a101->id.'][status]"', false)
            ->assertSee('name="sel[]"', false)
            ->assertSee('Guardar cambios');
    }

    public function test_cambia_dos_viviendas_de_golpe_y_la_que_no_se_toco_sigue_igual(): void
    {
        $this->guardar(['v' => [
            $this->a101->id => ['price' => '190000', 'status' => 'reserved'],
            $this->a102->id => ['price' => '214500', 'status' => 'sold'],
            $this->b201->id => ['price' => '', 'status' => 'available'],
        ]])->assertRedirect(route('admin.projects.units.index', $this->proyecto))
            ->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 2 && $r['sin_cambios'] === 1 && $r['errores'] === []);

        $this->assertSame(190000.0, $this->a101->fresh()->price);
        $this->assertSame('reserved', $this->a101->fresh()->status);
        $this->assertSame('sold', $this->a102->fresh()->status);
        $this->assertNull($this->b201->fresh()->price);
    }

    public function test_la_fila_con_precio_invalido_se_explica_y_las_demas_se_guardan(): void
    {
        $this->guardar(['v' => [
            $this->a101->id => ['price' => 'ciento ochenta', 'status' => 'available'],
            $this->a102->id => ['price' => '200000', 'status' => 'available'],
        ]])->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 1
            && $r['errores'] === [['vivienda' => 'A-101', 'motivo' => 'el precio no es un número válido']]);

        $this->assertSame(185000.0, $this->a101->fresh()->price, 'la fila mala no se guarda a medias');
        $this->assertSame(200000.0, $this->a102->fresh()->price);

        $this->actingAs($this->promotora)->get(route('admin.projects.units.index', $this->proyecto))
            ->assertSee('A-101: el precio no es un número válido');
    }

    public function test_el_agente_solo_puede_reservar_y_no_toca_precios(): void
    {
        $agente = User::factory()->create(['role' => User::ROLE_AGENTE, 'agency_id' => $this->promotora->id]);

        $this->guardar(['v' => [
            $this->a101->id => ['status' => 'reserved'],
            $this->a102->id => ['status' => 'sold'],
            $this->b201->id => ['price' => '99', 'status' => 'available'],
        ]], $agente)->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 1
            && array_column($r['errores'], 'motivo') === ['los agentes solo pueden reservar', 'los agentes no cambian precios']);

        $this->assertSame('reserved', $this->a101->fresh()->status);
        $this->assertSame('available', $this->a102->fresh()->status);
        $this->assertNull($this->b201->fresh()->price);
    }

    public function test_en_lote_sube_el_precio_un_porcentaje_y_explica_la_que_no_tiene_precio(): void
    {
        $this->guardar([
            'sel' => [$this->a101->id, $this->b201->id],
            'lote_accion' => 'porcentaje',
            'lote_porcentaje' => '5',
        ])->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 1
            && $r['errores'] === [['vivienda' => 'B-201', 'motivo' => 'no tiene precio, y un porcentaje de nada es nada']]);

        $this->assertSame(194250.0, $this->a101->fresh()->price);
        $this->assertSame(214500.0, $this->a102->fresh()->price, 'la que no estaba marcada no cambia');
    }

    public function test_en_lote_marca_varias_como_vendidas(): void
    {
        $this->guardar([
            'sel' => [$this->a101->id, $this->a102->id],
            'lote_accion' => 'estado',
            'lote_estado' => 'sold',
        ])->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 2);

        $this->assertSame(['sold', 'sold', 'available'], Unit::orderBy('identifier')->pluck('status')->all());
    }

    public function test_un_lote_sin_viviendas_marcadas_lo_dice_en_vez_de_no_hacer_nada(): void
    {
        $this->guardar(['lote_accion' => 'estado', 'lote_estado' => 'sold'])
            ->assertSessionHasErrors('sel');
        $this->assertSame('available', $this->a101->fresh()->status);
    }

    public function test_una_vivienda_de_otro_proyecto_no_se_toca_aunque_venga_en_el_envio(): void
    {
        $otra = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        $this->empresa($otra, 'otra');
        $ajeno = Project::create(['name' => 'Ajeno', 'slug' => 'ajeno', 'status' => 'draft', 'created_by' => $otra->id]);
        $ajena = Unit::create(['project_id' => $ajeno->id, 'identifier' => 'Z-1', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 1, 'area_m2' => 50, 'price' => 100, 'status' => 'available']);

        $this->guardar(['v' => [$ajena->id => ['price' => '1', 'status' => 'sold']]])
            ->assertSessionHas('edicion_rapida', fn ($r) => $r['guardadas'] === 0 && $r['errores'] === []);

        $this->assertSame(100.0, $ajena->fresh()->price);
        $this->assertSame('available', $ajena->fresh()->status);
    }

    public function test_quien_no_es_del_proyecto_no_puede_guardar(): void
    {
        $otra = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        $this->empresa($otra, 'otra');
        $this->guardar(['v' => [$this->a101->id => ['status' => 'sold']]], $otra)->assertForbidden();
        $this->assertSame('available', $this->a101->fresh()->status);
    }
}
