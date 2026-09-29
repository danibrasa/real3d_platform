<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CompanyProfile;
use App\Models\Inquiry;
use App\Models\MaterialDelProyecto;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Unit;
use App\Models\User;
use App\Models\ViewerEvent;
use App\Support\Pilotos\Embudo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los cinco numeros de cada piloto, sacados de lo que ya se guarda: si el
 * embudo miente, el ciclo semanal decide sobre humo.
 */
class PilotosTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = $this->promotora('ana', now()->subDays(20));
        $this->proyecto = Project::create(['name' => 'Residencial Bahia', 'slug' => 'residencial-bahia', 'status' => 'public', 'created_by' => $this->ana->id,
            'viewer_requested_at' => now()->subDays(15), 'visor_estado' => 'montado', 'visor_estado_en' => now()->subDays(10)]);
        $this->proyecto->forceFill(['created_at' => now()->subDays(18)])->save();
        $this->ana->assignedProjects()->attach($this->proyecto->id);
        Unit::create(['project_id' => $this->proyecto->id, 'identifier' => 'A-101', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'area_m2' => 70, 'price' => 100000, 'status' => 'available']);
    }

    private function promotora(string $nombre, $alta): User
    {
        $u = User::factory()->create(['role' => User::ROLE_INMOBILIARIA, 'email' => $nombre.'@ejemplo.invalid', 'name' => ucfirst($nombre)]);
        $u->forceFill(['created_at' => $alta])->save();
        CompanyProfile::create(['user_id' => $u->id, 'company_name' => 'Promotora '.ucfirst($nombre), 'slug' => $nombre, 'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL, 'max_projects' => 5]);

        return $u;
    }

    private function auditoria(string $accion, $cuando): void
    {
        // created_at no es fillable: se pone despues, como en el resto de fixtures.
        AuditLog::create(['action' => $accion, 'auditable_type' => $this->proyecto->getMorphClass(), 'auditable_id' => $this->proyecto->id])
            ->forceFill(['created_at' => $cuando, 'updated_at' => $cuando])->save();
    }

    private function lead(string $nombre, $cuando, ?string $estado = null, $contestado = null): void
    {
        // estado y estado_en no son fillable (los pone el controlador): forceFill.
        Inquiry::create(['project_id' => $this->proyecto->id, 'name' => $nombre, 'email' => strtolower($nombre).'@ejemplo.invalid'])
            ->forceFill(['created_at' => $cuando, 'estado' => $estado ?? 'nuevo', 'estado_en' => $contestado, 'contestado_en' => $contestado])->save();
    }

    public function test_los_cinco_numeros_de_una_promotora_en_marcha(): void
    {
        $this->auditoria('viewer_ready', now()->subDays(10));
        $this->auditoria('project_published', now()->subDays(8));
        $this->lead('Uno', now()->subDays(6), 'contactado', now()->subDays(6)->addHours(2));
        $this->lead('Dos', now()->subDays(5), 'contactado', now()->subDays(5)->addHours(30));
        $this->lead('Tres', now()->subDays(2));
        $this->lead('Viejo', now()->subDays(12), 'cerrado', now()->subDays(11));
        foreach (range(1, 4) as $i) {
            ViewerEvent::create(['project_id' => $this->proyecto->id, 'session_id' => "s$i", 'event_type' => 'session_start', 'created_at' => now()->subDays(3)]);
        }

        $e = Embudo::de($this->ana);

        $this->assertSame(5, $e['dias_alta_a_pedido'], 'alta hace 20, pedido hace 15');
        $this->assertSame(7, $e['dias_pedido_a_publicado'], 'pedido hace 15, publicado hace 8');
        $this->assertFalse($e['publicado_sin_fecha']);
        $this->assertSame(4, $e['leads']);
        $this->assertSame(3, $e['leads_semana']);
        $this->assertSame(3, $e['leads_contestados']);
        $this->assertSame(2, $e['leads_en_el_dia'], 'el de 30 horas no cuenta como en el dia');
        $this->assertSame(4, $e['visitas_30d']);
        $this->assertSame('en_marcha', $e['etapa']);
        $this->assertSame(2, $e['dias_en_etapa'], 'desde el ultimo lead');
    }

    public function test_publicado_sin_fecha_anotada_se_dice_y_no_se_inventa(): void
    {
        // Dato de antes de que se anotara: publico, sin project_published.
        AuditLog::where('action', 'project_published')->delete();
        $this->auditoria('viewer_ready', now()->subDays(10));
        $this->proyecto->update(['name' => 'Residencial Bahia II']); // un retoque no es una publicacion

        $e = Embudo::de($this->ana);

        $this->assertNull($e['publicado']);
        $this->assertTrue($e['publicado_sin_fecha']);
        $this->assertNull($e['dias_pedido_a_publicado']);
        $this->assertSame('sin_leads', $e['etapa']);
        $this->assertNull($e['dias_en_etapa']);
    }

    public function test_los_tiempos_son_de_un_solo_proyecto_y_nunca_negativos(): void
    {
        // Bahia: publicado hace 8 sin visor pedido. Otro: visor pedido ayer.
        // Mezclarlos daba "de pedido a publicado" negativo.
        $this->auditoria('project_published', now()->subDays(8));
        $this->proyecto->update(['viewer_requested_at' => null]);
        $otro = Project::create(['name' => 'Otro', 'slug' => 'otro', 'status' => 'draft', 'created_by' => $this->ana->id, 'viewer_requested_at' => now()->subDay()]);
        $this->ana->assignedProjects()->attach($otro->id);

        $e = Embudo::de($this->ana);
        $this->assertNull($e['dias_pedido_a_publicado'], 'la publicacion de un proyecto no se mide contra el pedido de otro');
        $this->assertSame($this->proyecto->id, $e['proyecto']->id, 'el de referencia es el que mas lejos llego');
        $this->assertSame('sin_leads', $e['etapa'], 'con un proyecto publico no esta "esperando al equipo"');

        // Visor pedido antes de dar de alta a la promotora: cero, no negativo.
        $this->ana->forceFill(['created_at' => now()->subDays(2)])->save();
        $otro->update(['viewer_requested_at' => now()->subDays(5)]);
        $this->proyecto->update(['viewer_requested_at' => now()->subDays(5)]);
        $e = Embudo::de($this->ana->fresh());
        $this->assertSame(0, $e['dias_alta_a_pedido']);
        // Bahia se publico hace 8 y el visor se pidio hace 5: no son "0 dias
        // de pedido a publicado", es que no hay dato.
        $this->assertNull($e['dias_pedido_a_publicado']);
    }

    public function test_el_reloj_de_la_etapa_es_el_proyecto_que_mas_lleva_esperando(): void
    {
        $this->proyecto->update(['status' => 'draft', 'visor_estado' => 'pedido', 'visor_estado_en' => now()->subDays(30), 'viewer_requested_at' => now()->subDays(30)]);
        $otro = Project::create(['name' => 'Otro', 'slug' => 'otro', 'status' => 'draft', 'created_by' => $this->ana->id, 'viewer_requested_at' => now()->subDay()]);
        $this->ana->assignedProjects()->attach($otro->id);

        $e = Embudo::de($this->ana);
        $this->assertSame('esperando_equipo', $e['etapa']);
        $this->assertSame(30, $e['dias_en_etapa'], 'un pedido de ayer no borra un atasco de treinta dias');
    }

    public function test_la_primera_respuesta_se_anota_por_cualquier_camino(): void
    {
        $lead = Inquiry::create(['project_id' => $this->proyecto->id, 'name' => 'Uno', 'email' => 'uno@ejemplo.invalid']);
        $this->assertNull($lead->contestado_en);

        $this->travelTo(now()->addHours(3));
        $lead->forceFill(['estado' => 'contactado', 'estado_en' => now()])->save(); // sin pasar por el panel
        $primera = $lead->fresh()->contestado_en;
        $this->assertNotNull($primera);

        $this->travelTo(now()->addDays(7));
        $lead->forceFill(['estado' => 'cerrado', 'estado_en' => now()])->save();
        $this->assertTrue($primera->equalTo($lead->fresh()->contestado_en), 'la primera respuesta no se mueve con el siguiente cambio');
        $this->travelBack();
    }

    public function test_despublicar_devuelve_la_etapa_atras(): void
    {
        $this->auditoria('viewer_ready', now()->subDays(10));
        $this->auditoria('project_published', now()->subDays(8));
        $this->assertSame('sin_leads', Embudo::de($this->ana)['etapa']);

        $this->proyecto->update(['status' => 'draft']);
        $this->assertSame('sin_publicar', Embudo::de($this->ana)['etapa']);
    }

    public function test_en_el_dia_mide_la_primera_respuesta_y_no_el_ultimo_cambio(): void
    {
        $this->auditoria('project_published', now()->subDays(8));
        $lead = Inquiry::create(['project_id' => $this->proyecto->id, 'name' => 'Uno', 'email' => 'uno@ejemplo.invalid']);
        $lead->forceFill(['created_at' => now()->subDays(6)])->save();

        $this->travelTo(now()->subDays(6)->addHours(2));
        $this->actingAs($this->ana)->patch(route('admin.inquiries.estado', $lead), ['estado' => 'contactado']);
        $this->travelBack();
        $this->actingAs($this->ana)->patch(route('admin.inquiries.estado', $lead), ['estado' => 'cerrado']);

        $lead->refresh();
        $this->assertSame('cerrado', $lead->estado);
        $this->assertEqualsWithDelta(2, $lead->created_at->diffInHours($lead->contestado_en), 0.1, 'contestado_en es la primera respuesta');
        $this->assertSame(1, Embudo::de($this->ana)['leads_en_el_dia']);
    }

    public function test_la_etapa_dice_donde_se_ha_quedado_cada_una(): void
    {
        $nueva = $this->promotora('nueva', now()->subDays(3));
        $this->assertSame('sin_proyecto', Embudo::de($nueva)['etapa']);
        $this->assertSame(3, Embudo::de($nueva)['dias_en_etapa']);

        $p = Project::create(['name' => 'Vacio', 'slug' => 'vacio', 'status' => 'draft', 'created_by' => $nueva->id]);
        $nueva->assignedProjects()->attach($p->id);
        $this->assertSame('sin_viviendas', Embudo::de($nueva)['etapa']);

        Unit::create(['project_id' => $p->id, 'identifier' => 'B-1', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 1, 'area_m2' => 50, 'price' => 1, 'status' => 'available']);
        $this->assertSame('sin_material', Embudo::de($nueva)['etapa']);

        MaterialDelProyecto::create(['project_id' => $p->id, 'tipo' => 'planos', 'original_name' => 'planos (enlace)', 'subido_por' => $nueva->id, 'enlace' => 'https://ejemplo.invalid/planos']);
        $this->assertSame('sin_pedir_visor', Embudo::de($nueva)['etapa']);

        $p->update(['viewer_requested_at' => now()]);
        $this->assertSame('esperando_equipo', Embudo::de($nueva)['etapa']);

        $p->update(['visor_estado' => 'montado', 'visor_estado_en' => now()]);
        $this->assertSame('sin_publicar', Embudo::de($nueva)['etapa']);

        $p->update(['status' => 'public']);
        $this->assertSame('sin_leads', Embudo::de($nueva)['etapa']);
    }

    public function test_publicar_un_proyecto_deja_la_fecha_en_la_auditoria(): void
    {
        $this->proyecto->update(['status' => 'draft']);
        AuditLog::where('action', 'project_published')->delete(); // el del setUp
        // Con visor: sin el, la lista para publicar lo para antes.
        ProjectFile::create(['project_id' => $this->proyecto->id, 'file_type' => 'image_360', 'original_name' => 'f.jpg', 'storage_path' => 'x/f.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 5, 'upload_complete' => true]);

        $this->actingAs($this->ana)->put(route('admin.projects.update', $this->proyecto), ['name' => 'Residencial Bahia', 'status' => 'public'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, AuditLog::where('action', 'project_published')->where('auditable_id', $this->proyecto->id)->count());

        // Publicar dos veces no es publicar dos veces.
        $this->actingAs($this->ana)->put(route('admin.projects.update', $this->proyecto), ['name' => 'Residencial Bahia', 'status' => 'public'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, AuditLog::where('action', 'project_published')->where('auditable_id', $this->proyecto->id)->count());

        // Y crearlo ya publico tambien lo anota: la fecha se pone desde el modelo.
        $nuevo = Project::create(['name' => 'Directo', 'slug' => 'directo', 'status' => 'public', 'created_by' => $this->ana->id]);
        $this->assertSame(1, AuditLog::where('action', 'project_published')->where('auditable_id', $nuevo->id)->count());
    }

    public function test_la_pagina_es_del_equipo_y_el_informe_dice_lo_mismo(): void
    {
        $this->auditoria('project_published', now()->subDays(8));
        $gestora = User::factory()->create(['role' => User::ROLE_GESTOR]);

        $this->actingAs($this->ana)->get(route('admin.pilotos.index'))->assertForbidden();
        // Otra promotora en otra etapa y con la fila DEBAJO de la de Ana (la
        // lista va por alta, nuevas arriba): con la regex sin barrera de fila,
        // el negativo de abajo pasaria a la fila siguiente y fallaria.
        $this->promotora('vieja', now()->subDays(40));
        $html = $this->actingAs($gestora)->get(route('admin.pilotos.index'))->assertOk()->getContent();
        $fila = fn ($id) => '/data-piloto="'.$id.'"(?:(?!<\/tr>).)*?';
        $this->assertMatchesRegularExpression($fila($this->ana->id).'Publicado, sin leads/s', $html);
        $this->assertDoesNotMatchRegularExpression($fila($this->ana->id).'Sin proyecto/s', $html);

        $this->artisan('pilotos:informe')->expectsOutputToContain('Promotora Ana (professional)')->assertSuccessful();
        $this->artisan('pilotos:informe')->expectsOutputToContain('sin_leads (')->assertSuccessful();
    }
}
