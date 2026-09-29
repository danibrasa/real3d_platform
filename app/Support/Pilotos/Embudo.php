<?php

namespace App\Support\Pilotos;

use App\Models\AuditLog;
use App\Models\Inquiry;
use App\Models\MaterialDelProyecto;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Support\Visor\ResumenDeTreintaDias;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Lo que vamos a mirar de cada piloto, con los datos que ya se guardan.
 *
 * Cinco cosas: cuanto tarda de darse de alta a pedir el visor, cuanto de
 * pedirlo a publicar, cuantos leads le llegan a la semana, cuantos
 * contesta (y si en el dia), y en que paso se ha quedado. No hay nada que
 * instrumentar de nuevo: son las fechas de las tablas y el registro de
 * auditoria (viewer_ready, project_published). Lo que no consta se dice
 * como "no consta", no como cero.
 */
class Embudo
{
    public const ETAPAS = ['sin_proyecto', 'sin_viviendas', 'sin_material', 'sin_pedir_visor', 'esperando_equipo', 'sin_publicar', 'sin_leads', 'en_marcha'];

    public const HORAS_PARA_CONTESTAR = 24;

    /**
     * @return array{
     *   promotora: User, plan: ?string, alta: Carbon, proyectos: int,
     *   visor_pedido: ?Carbon, visor_montado: ?Carbon, publicado: ?Carbon, publicado_aproximado: bool,
     *   dias_alta_a_pedido: ?int, dias_pedido_a_publicado: ?int,
     *   leads: int, leads_semana: int, leads_contestados: int, leads_en_el_dia: int,
     *   visitas_30d: int, etapa: string, etapa_desde: ?Carbon, dias_en_etapa: ?int
     * }
     */
    public static function de(User $promotora): array
    {
        $proyectos = $promotora->assignedProjects()->get();
        $ids = $proyectos->pluck('id');
        $tipo = (new Project)->getMorphClass();

        $primerProyecto = self::fecha($proyectos->min('created_at'));
        $pedido = self::fecha($proyectos->whereNotNull('viewer_requested_at')->min('viewer_requested_at'));

        // Montado: cuando el equipo lo dio por montado (viewer_ready). Si el
        // dato es de antes de la auditoria, el reloj del estado.
        $montado = self::fecha(AuditLog::where('action', 'viewer_ready')->where('auditable_type', $tipo)->whereIn('auditable_id', $ids)->min('created_at'))
            ?? self::fecha($proyectos->where('visor_estado', Project::VISOR_MONTADO)->min('visor_estado_en'));

        $publicado = self::fecha(AuditLog::where('action', 'project_published')->where('auditable_type', $tipo)->whereIn('auditable_id', $ids)->min('created_at'));
        $publicadoAproximado = false;
        if (! $publicado && ($publicos = $proyectos->where('status', 'public'))->isNotEmpty()) {
            // Publicado antes de que se anotara: la ultima modificacion es lo
            // mas cerca que hay, y se enseña como aproximado.
            $publicado = self::fecha($publicos->min('updated_at'));
            $publicadoAproximado = true;
        }

        $leads = Inquiry::whereIn('project_id', $ids)->get(['created_at', 'estado', 'estado_en']);
        $contestados = $leads->filter(fn ($l) => $l->estado !== null && $l->estado !== 'nuevo' && $l->estado_en !== null);
        $enElDia = $contestados->filter(fn ($l) => $l->created_at->diffInHours($l->estado_en, false) <= self::HORAS_PARA_CONTESTAR);

        $unidades = Unit::whereIn('project_id', $ids)->max('created_at');
        $material = MaterialDelProyecto::whereIn('project_id', $ids)->max('created_at');
        $ultimoLead = $leads->max('created_at');

        [$etapa, $desde] = self::etapa([
            'proyectos' => $proyectos->count(), 'alta' => $promotora->created_at, 'primer_proyecto' => $primerProyecto,
            'unidades' => self::fecha($unidades), 'material' => self::fecha($material), 'pedido' => $pedido,
            'montado' => $montado, 'publicado' => $publicado, 'leads' => $leads->count(), 'ultimo_lead' => self::fecha($ultimoLead),
        ]);

        return [
            'promotora' => $promotora,
            'plan' => $promotora->companyProfile?->plan_tier,
            'alta' => $promotora->created_at,
            'proyectos' => $proyectos->count(),
            'visor_pedido' => $pedido,
            'visor_montado' => $montado,
            'publicado' => $publicado,
            'publicado_aproximado' => $publicadoAproximado,
            'dias_alta_a_pedido' => $pedido ? (int) $promotora->created_at->diffInDays($pedido) : null,
            'dias_pedido_a_publicado' => ($pedido && $publicado) ? (int) $pedido->diffInDays($publicado) : null,
            'leads' => $leads->count(),
            'leads_semana' => $leads->filter(fn ($l) => $l->created_at->gte(now()->subDays(7)))->count(),
            'leads_contestados' => $contestados->count(),
            'leads_en_el_dia' => $enElDia->count(),
            'visitas_30d' => $ids->isEmpty() ? 0 : ResumenDeTreintaDias::de($ids)['visitas'],
            'etapa' => $etapa,
            'etapa_desde' => $desde,
            'dias_en_etapa' => $desde ? (int) $desde->diffInDays(now()) : null,
        ];
    }

    /** En que paso esta (o se ha quedado), y desde cuando. */
    private static function etapa(array $d): array
    {
        if ($d['proyectos'] === 0) {
            return ['sin_proyecto', $d['alta']];
        }
        if (! $d['unidades']) {
            return ['sin_viviendas', $d['primer_proyecto']];
        }
        if (! $d['material'] && ! $d['pedido']) {
            return ['sin_material', $d['unidades']];
        }
        if (! $d['pedido']) {
            return ['sin_pedir_visor', $d['material']];
        }
        if (! $d['montado']) {
            return ['esperando_equipo', $d['pedido']];
        }
        if (! $d['publicado']) {
            return ['sin_publicar', $d['montado']];
        }
        if ($d['leads'] === 0) {
            return ['sin_leads', $d['publicado']];
        }

        return ['en_marcha', $d['ultimo_lead']];
    }

    /** Todas las promotoras, ordenadas por alta (las nuevas arriba). */
    public static function deTodas(): array
    {
        return User::where('role', User::ROLE_INMOBILIARIA)->with('companyProfile')->orderByDesc('created_at')->get()
            ->map(fn (User $u) => self::de($u))->all();
    }

    private static function fecha(mixed $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        return $valor instanceof CarbonInterface ? Carbon::instance($valor) : Carbon::parse($valor);
    }
}
