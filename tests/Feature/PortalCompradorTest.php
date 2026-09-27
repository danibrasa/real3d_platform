<?php

namespace Tests\Feature;

use App\Models\BuyerPayment;
use App\Models\CompanyProfile;
use App\Models\ConstructionPhase;
use App\Models\ConstructionUpdate;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El portal de quien ya compro.
 *
 * Entre la firma y la entrega pasan dos o tres años en los que el comprador
 * solo tiene recibos y confianza. Lo que mas importa aqui es que nadie vea lo
 * que no es suyo: son datos de pagos de personas concretas.
 */
class PortalCompradorTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private User $comprador;

    private Project $proyecto;

    private Unit $vivienda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promotora = User::factory()->create(['role' => 'superadmin']);
        $this->comprador = User::factory()->create(['role' => 'user', 'email' => 'ana@ejemplo.com']);

        $this->proyecto = Project::create([
            'name' => 'Torre Caribe',
            'slug' => 'torre-caribe',
            'status' => 'public',
            'location' => 'Punta Cana',
            'created_by' => $this->promotora->id,
        ]);

        $this->vivienda = Unit::create([
            'project_id' => $this->proyecto->id,
            'identifier' => 'A-101',
            'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2,
            'area_m2' => 85, 'price' => 185000,
            'status' => 'sold', 'sort_order' => 1,
            'buyer_id' => $this->comprador->id,
        ]);
    }

    // --- Lo que ve el comprador -------------------------------------------

    public function test_el_comprador_ve_su_vivienda(): void
    {
        $this->actingAs($this->comprador)
            ->get(route('mi-inversion.show', $this->vivienda).'?lang=es')
            ->assertOk()
            ->assertSee('A-101')
            ->assertSee('Torre Caribe');
    }

    public function test_ve_lo_que_lleva_pagado(): void
    {
        BuyerPayment::create([
            'unit_id' => $this->vivienda->id,
            'concept' => 'Reserva',
            'amount' => 5000,
            'paid_on' => '2026-01-15',
            'reference' => 'TRF-001',
        ]);
        BuyerPayment::create([
            'unit_id' => $this->vivienda->id,
            'concept' => 'Primer plazo',
            'amount' => 32000,
            'paid_on' => '2026-03-01',
        ]);

        $r = $this->actingAs($this->comprador)
            ->get(route('mi-inversion.show', $this->vivienda).'?lang=es');

        $r->assertOk()
            ->assertSee('Reserva')
            ->assertSee('TRF-001')
            ->assertSee('37.000,00')   // total pagado
            ->assertSee('148.000,00'); // lo que falta
    }

    public function test_con_una_sola_vivienda_entra_directo(): void
    {
        // Una lista de un elemento no aporta nada.
        $this->actingAs($this->comprador)
            ->get(route('mi-inversion.index'))
            ->assertRedirect(route('mi-inversion.show', $this->vivienda));
    }

    public function test_con_varias_viviendas_puede_elegir(): void
    {
        Unit::create([
            'project_id' => $this->proyecto->id, 'identifier' => 'B-202',
            'floor' => 2, 'bedrooms' => 3, 'bathrooms' => 2, 'area_m2' => 110,
            'price' => 240000, 'status' => 'sold', 'sort_order' => 2,
            'buyer_id' => $this->comprador->id,
        ]);

        $this->actingAs($this->comprador)
            ->get(route('mi-inversion.index').'?lang=es')
            ->assertOk()
            ->assertSee('A-101')
            ->assertSee('B-202');
    }

    // --- Lo que NO puede ver nadie mas ------------------------------------

    public function test_otro_usuario_no_puede_ver_una_inversion_ajena(): void
    {
        // Lo mas importante de todo esto: son datos de pagos de una persona.
        $otro = User::factory()->create(['role' => 'user']);

        $this->actingAs($otro)
            ->get(route('mi-inversion.show', $this->vivienda))
            ->assertForbidden();
    }

    public function test_ni_siquiera_un_administrador_entra_por_aqui(): void
    {
        // La promotora tiene su propio panel; este portal es del comprador.
        $this->actingAs($this->promotora)
            ->get(route('mi-inversion.show', $this->vivienda))
            ->assertForbidden();
    }

    public function test_un_visitante_tiene_que_identificarse(): void
    {
        $this->get(route('mi-inversion.show', $this->vivienda))
            ->assertRedirect(route('login'));
    }

    public function test_quien_no_ha_comprado_nada_no_tiene_portal(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('mi-inversion.index'))
            ->assertForbidden();
    }

    // --- El lado de la promotora ------------------------------------------

    public function test_la_promotora_asigna_un_comprador_nuevo(): void
    {
        $libre = Unit::create([
            'project_id' => $this->proyecto->id, 'identifier' => 'C-303',
            'floor' => 3, 'bedrooms' => 1, 'bathrooms' => 1, 'area_m2' => 60,
            'price' => 120000, 'status' => 'available', 'sort_order' => 3,
        ]);

        $this->actingAs($this->promotora)
            ->post(route('admin.projects.units.comprador.asignar', [$this->proyecto, $libre]), [
                'name' => 'Carlos Ruiz',
                'email' => 'CARLOS@Ejemplo.com',
                'sold_at' => '2026-09-01',
            ])
            ->assertRedirect();

        // Se crea la cuenta con el correo en minusculas
        $nuevo = User::where('email', 'carlos@ejemplo.com')->first();
        $this->assertNotNull($nuevo, 'no se ha creado la cuenta del comprador');

        $libre->refresh();
        $this->assertSame($nuevo->id, $libre->buyer_id);
        $this->assertSame('sold', $libre->status, 'la vivienda deberia quedar vendida');
        $this->assertSame('2026-09-01', $libre->sold_at->toDateString());
    }

    public function test_no_duplica_la_cuenta_si_el_correo_ya_existe(): void
    {
        $otraVivienda = Unit::create([
            'project_id' => $this->proyecto->id, 'identifier' => 'D-404',
            'floor' => 4, 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 75,
            'price' => 150000, 'status' => 'available', 'sort_order' => 4,
        ]);

        $antes = User::count();

        $this->actingAs($this->promotora)
            ->post(route('admin.projects.units.comprador.asignar', [$this->proyecto, $otraVivienda]), [
                'name' => 'Ana otra vez',
                'email' => 'ana@ejemplo.com',
            ]);

        $this->assertSame($antes, User::count(), 'ha duplicado la cuenta');
        $this->assertSame($this->comprador->id, $otraVivienda->fresh()->buyer_id);
    }

    public function test_la_promotora_anota_un_pago_y_el_comprador_lo_ve(): void
    {
        $this->actingAs($this->promotora)
            ->post(route('admin.projects.units.comprador.pago', [$this->proyecto, $this->vivienda]), [
                'concept' => 'Segundo plazo',
                'amount' => 18500,
                'paid_on' => '2026-06-10',
                'reference' => 'TRF-042',
            ])
            ->assertRedirect();

        $this->actingAs($this->comprador)
            ->get(route('mi-inversion.show', $this->vivienda).'?lang=es')
            ->assertSee('Segundo plazo')
            ->assertSee('TRF-042');
    }

    public function test_no_admite_un_pago_con_fecha_futura(): void
    {
        $this->actingAs($this->promotora)
            ->post(route('admin.projects.units.comprador.pago', [$this->proyecto, $this->vivienda]), [
                'concept' => 'Adelanto',
                'amount' => 1000,
                'paid_on' => now()->addMonth()->toDateString(),
            ])
            ->assertSessionHasErrors('paid_on');

        $this->assertSame(0, $this->vivienda->payments()->count());
    }

    public function test_quitar_el_comprador_conserva_sus_pagos(): void
    {
        BuyerPayment::create([
            'unit_id' => $this->vivienda->id, 'concept' => 'Reserva',
            'amount' => 5000, 'paid_on' => '2026-01-15',
        ]);

        $this->actingAs($this->promotora)
            ->delete(route('admin.projects.units.comprador.desasignar', [$this->proyecto, $this->vivienda]));

        $this->assertNull($this->vivienda->fresh()->buyer_id);
        $this->assertSame(1, $this->vivienda->payments()->count(), 'se han perdido los pagos');
        $this->assertNotNull($this->comprador->fresh(), 'se ha borrado la cuenta del comprador');
    }

    public function test_un_usuario_normal_no_puede_asignar_compradores(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->post(route('admin.projects.units.comprador.asignar', [$this->proyecto, $this->vivienda]), [
                'name' => 'Intruso', 'email' => 'intruso@ejemplo.com',
            ])
            ->assertForbidden();
    }

    // --- El avance de obra -------------------------------------------------

    public function test_ve_como_va_la_obra(): void
    {
        $fase = ConstructionPhase::create([
            'project_id' => $this->proyecto->id,
            'name' => 'Estructura',
            'target_percentage' => 40,
            'status' => 'in_progress',
            'sort_order' => 1,
        ]);

        ConstructionUpdate::create([
            'project_id' => $this->proyecto->id,
            'construction_phase_id' => $fase->id,
            'title' => 'Estructura hasta planta 6',
            'description' => 'Terminada la estructura hasta la planta 6.',
            'date' => '2026-09-01',
            'progress_percentage' => 42,
            'created_by' => $this->promotora->id,
        ]);

        $this->actingAs($this->comprador)
            ->get(route('mi-inversion.show', $this->vivienda).'?lang=es')
            ->assertOk()
            ->assertSee('42%')
            ->assertSee('Estructura')
            ->assertSee('planta 6');
    }

    public function test_sin_avance_publicado_lo_dice_en_vez_de_callar(): void
    {
        // Un hueco vacio deja al comprador sin saber si es que no hay obra o si
        // es que la pagina esta rota.
        $this->actingAs($this->comprador)
            ->get(route('mi-inversion.show', $this->vivienda).'?lang=es')
            ->assertOk()
            ->assertSee('todavía no ha publicado', false);
    }

    public function test_el_porcentaje_sale_de_la_ultima_actualizacion(): void
    {
        // Toda actualizacion pertenece a una fase: lo exige la tabla.
        $fase = ConstructionPhase::create([
            'project_id' => $this->proyecto->id,
            'name' => 'Cimentacion',
            'target_percentage' => 50,
            'status' => 'in_progress',
            'sort_order' => 1,
        ]);

        foreach ([['2026-03-01', 15], ['2026-09-01', 42]] as [$fecha, $avance]) {
            ConstructionUpdate::create([
                'project_id' => $this->proyecto->id,
                'construction_phase_id' => $fase->id,
                'title' => 'Avance',
                'date' => $fecha,
                'progress_percentage' => $avance,
                'created_by' => $this->promotora->id,
            ]);
        }

        // Manda la mas reciente, no la primera ni la media.
        $this->actingAs($this->comprador)
            ->get(route('mi-inversion.show', $this->vivienda).'?lang=es')
            ->assertSee('42%');
    }

    public function test_una_promotora_no_ve_los_compradores_de_otra(): void
    {
        // Quien compro y cuanto lleva pagado son datos de una persona, y ademas
        // informacion comercial de la competencia.
        $otra = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $otra->id,
            'company_name' => 'Otra inmobiliaria',
            'slug' => 'otra-inmobiliaria',
            'plan_tier' => 'professional',
            'max_projects' => 5,
        ]);

        $this->actingAs($otra)
            ->get(route('admin.projects.units.comprador', [$this->proyecto, $this->vivienda]))
            ->assertForbidden();
    }

    public function test_una_promotora_no_puede_anotar_pagos_en_un_proyecto_ajeno(): void
    {
        $otra = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $otra->id,
            'company_name' => 'Otra mas',
            'slug' => 'otra-mas',
            'plan_tier' => 'professional',
            'max_projects' => 5,
        ]);

        $this->actingAs($otra)
            ->post(route('admin.projects.units.comprador.pago', [$this->proyecto, $this->vivienda]), [
                'concept' => 'Inventado',
                'amount' => 1000,
                'paid_on' => '2026-01-01',
            ])
            ->assertForbidden();

        $this->assertSame(0, $this->vivienda->payments()->count());
    }
}
