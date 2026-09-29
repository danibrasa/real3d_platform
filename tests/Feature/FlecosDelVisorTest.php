<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Unit;
use App\Models\User;
use App\Models\ViewerEvent;
use App\Support\Visor\ResumenDeTreintaDias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tres cosas del visor que se ven desde PHP: sin WebGL la ficha sigue
 * vendiendo, un enlace a una vivienda se ve como esa vivienda al pegarlo en
 * WhatsApp, y la promotora tiene en el panel tres numeros que cuadran.
 */
class FlecosDelVisorTest extends TestCase
{
    use RefreshDatabase;

    private User $promotora;

    private Project $proyecto;

    private Unit $vivienda;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        $this->promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $this->promotora->id, 'company_name' => 'Promotora Bahia', 'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL, 'max_projects' => 5,
        ]);
        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'public', 'created_by' => $this->promotora->id,
        ]);
        $this->promotora->assignedProjects()->attach($this->proyecto->id);
        Storage::put('x/fondo.jpg', 'fondo');
        ProjectFile::create([
            'project_id' => $this->proyecto->id, 'file_type' => 'image_360', 'original_name' => 'fondo.jpg',
            'storage_path' => 'x/fondo.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 5, 'upload_complete' => true,
        ]);
        $this->vivienda = Unit::create([
            'project_id' => $this->proyecto->id, 'identifier' => 'A-102', 'floor' => 1, 'bedrooms' => 2,
            'bathrooms' => 2, 'area_m2' => 85, 'price' => 250000, 'status' => 'available', 'sort_order' => 1,
        ]);
    }

    // --- 3.4: sin WebGL -----------------------------------------------------------

    public function test_sin_webgl_el_visor_manda_a_la_ficha_y_la_ficha_lo_explica(): void
    {
        $ficha = route('viewer.landing', $this->proyecto).'?sin3d=1';

        // La comprobacion va en la pagina, antes de cargar nada pesado, y
        // manda a la ficha con un motivo.
        $this->get(route('viewer.show', $this->proyecto))
            ->assertOk()
            ->assertSee("getContext('webgl')", false)
            // @json escapa las barras: se mira el motivo y la orden, no la URL entera.
            ->assertSee('location.replace(', false)
            ->assertSee('info?sin3d=1', false);

        $this->get($ficha)->assertOk()->assertSee(__('visor.sin3d_titulo'));
        $this->get(route('viewer.landing', $this->proyecto))->assertOk()->assertDontSee(__('visor.sin3d_titulo'));
    }

    // --- 3.5: compartir una vivienda ------------------------------------------------

    public function test_el_enlace_a_una_vivienda_se_presenta_como_esa_vivienda(): void
    {
        $this->get(route('viewer.show', $this->proyecto).'?unit='.$this->vivienda->id)
            ->assertOk()
            ->assertSee('<meta property="og:title" content="A-102 · Residencial Bahia', false)
            ->assertSee('250', false);

        // Una vivienda de otro proyecto no se cuela por el numero.
        $otro = Project::create(['name' => 'Otro', 'slug' => 'otro', 'status' => 'public', 'created_by' => $this->promotora->id]);
        $ajena = Unit::create([
            'project_id' => $otro->id, 'identifier' => 'Z-9', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 1,
            'area_m2' => 40, 'price' => 90000, 'status' => 'available', 'sort_order' => 1,
        ]);
        $this->get(route('viewer.show', $this->proyecto).'?unit='.$ajena->id)
            ->assertOk()
            ->assertDontSee('Z-9')
            ->assertSee('<meta property="og:title" content="Residencial Bahia - Visor 3D"', false);
    }

    // --- 3.5: tres numeros en el panel -------------------------------------------------

    private function evento(string $tipo, string $sesion, ?int $unidad = null, int $haceDias = 1): void
    {
        ViewerEvent::create([
            'project_id' => $this->proyecto->id, 'session_id' => $sesion, 'event_type' => $tipo,
            'unit_id' => $unidad, 'created_at' => now()->subDays($haceDias),
        ]);
    }

    public function test_quien_no_atiende_compradores_no_ve_los_numeros(): void
    {
        $this->evento('session_start', 's1');
        Gate::define('view-inquiries', fn () => false);

        $this->actingAs($this->promotora)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(__('visor.resumen_titulo'));
    }

    public function test_los_tres_numeros_cuadran_con_lo_que_hay(): void
    {
        $otra = Unit::create([
            'project_id' => $this->proyecto->id, 'identifier' => 'B-201', 'floor' => 2, 'bedrooms' => 3,
            'bathrooms' => 2, 'area_m2' => 110, 'price' => 320000, 'status' => 'available', 'sort_order' => 2,
        ]);

        // Tres sesiones, una de ellas con dos arranques (una visita, no dos).
        $this->evento('session_start', 's1');
        $this->evento('session_start', 's1');
        $this->evento('session_start', 's2');
        $this->evento('session_start', 's3', null, 40);        // hace 40 dias: fuera
        // La A-102 la miran dos veces, la B-201 una.
        $this->evento('unit_selected', 's1', $this->vivienda->id);
        $this->evento('unit_selected', 's2', $this->vivienda->id);
        $this->evento('unit_selected', 's2', $otra->id);
        // Dos leads, uno viejo.
        Inquiry::create(['project_id' => $this->proyecto->id, 'name' => 'Uno', 'email' => 'uno@x.invalid', 'message' => 'Hola', 'read' => false]);
        Inquiry::create(['project_id' => $this->proyecto->id, 'name' => 'Dos', 'email' => 'dos@x.invalid', 'message' => 'Hola', 'read' => false])
            ->forceFill(['created_at' => now()->subDays(45)])->save();

        $resumen = ResumenDeTreintaDias::de(collect([$this->proyecto->id]));

        $this->assertSame(2, $resumen['visitas']);
        $this->assertSame([['identificador' => 'A-102', 'veces' => 2], ['identificador' => 'B-201', 'veces' => 1]], $resumen['mas_vistas']);
        $this->assertSame(1, $resumen['leads']);
    }

    public function test_el_panel_los_enseña_y_no_mezcla_promotoras(): void
    {
        $this->evento('session_start', 's1');
        $this->evento('unit_selected', 's1', $this->vivienda->id);

        // Lo de otra promotora no cuenta.
        $otra = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create(['user_id' => $otra->id, 'company_name' => 'Otra', 'slug' => 'otra', 'plan_tier' => CompanyProfile::PLAN_STARTER, 'max_projects' => 1]);
        $ajeno = Project::create(['name' => 'Ajeno', 'slug' => 'ajeno', 'status' => 'public', 'created_by' => $otra->id]);
        $otra->assignedProjects()->attach($ajeno->id);
        for ($i = 0; $i < 7; $i++) {
            ViewerEvent::create(['project_id' => $ajeno->id, 'session_id' => "a{$i}", 'event_type' => 'session_start', 'created_at' => now()]);
        }

        // El numero exacto dentro de su casilla, no un '1' suelto por la
        // pagina: lo dijo el revisor, y tenia razon.
        $this->actingAs($this->promotora)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('visor.resumen_titulo'))
            ->assertSee('tabular-nums">1</div>', false)
            ->assertDontSee('tabular-nums">7</div>', false)
            ->assertDontSee('tabular-nums">8</div>', false)
            ->assertSee('A-102');

        $this->assertSame(7, ResumenDeTreintaDias::de(collect([$ajeno->id]))['visitas']);
        $this->assertSame(1, ResumenDeTreintaDias::de(collect([$this->proyecto->id]))['visitas']);
    }
}
