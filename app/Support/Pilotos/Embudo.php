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
 * contesta (y si en el dia), y en que paso esta o se ha quedado. Salen de
 * las fechas de las tablas y del registro de auditoria (viewer_ready,
 * project_published). Lo que no consta se dice como "no consta", no como
 * cero ni como una fecha parecida.
 *
 * Los tiempos se miden en UN proyecto, el que mas lejos llego (el primero
 * publicado; si no, el primero montado; si no, el primero pedido): mezclar
 * el pedido de un proyecto con la publicacion de otro daba dias negativos.
 */
class Embudo
{
    public const ETAPAS = ['sin_proyecto', 'sin_viviendas', 'sin_material', 'sin_pedir_visor', 'esperando_equipo', 'sin_publicar', 'sin_leads', 'en_marcha'];

    public const HORAS_PARA_CONTESTAR = 24;

    /**
     * @return array{
     *   promotora: User, plan: ?string, alta: Carbon, proyectos: int, proyecto: ?Project,
     *   visor_pedido: ?Carbon, visor_montado: ?Carbon, publicado: ?Carbon, publicado_sin_fecha: bool,
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

        // Las fechas de la auditoria, por proyecto: cuando el equipo lo dio
        // por montado y cuando salio a la web.
        $auditoria = AuditLog::where('auditable_type', $tipo)->whereIn('auditable_id', $ids)
            ->whereIn('action', ['viewer_ready', 'project_published'])
            ->selectRaw('auditable_id, action, MIN(created_at) as primera')->groupBy('auditable_id', 'action')->get()
            ->groupBy('auditable_id');

        $hitos = $proyectos->map(function (Project $p) use ($auditoria) {
            $suya = $auditoria->get($p->id, collect())->keyBy('action');

            return [
                'proyecto' => $p,
                'pedido' => self::fecha($p->viewer_requested_at),
                'montado' => self::fecha($suya->get('viewer_ready')?->primera)
                    ?? ($p->visor_estado === Project::VISOR_MONTADO ? self::fecha($p->visor_estado_en) : null),
                'publicado' => self::fecha($suya->get('project_published')?->primera),
                'publico' => $p->status === 'public',
            ];
        });

        // El proyecto de referencia: el que mas lejos llego, y de esos el primero.
        $referencia = $hitos->filter(fn ($h) => $h['publicado'])->sortBy('publicado')->first()
            ?? $hitos->filter(fn ($h) => $h['montado'])->sortBy('montado')->first()
            ?? $hitos->filter(fn ($h) => $h['pedido'])->sortBy('pedido')->first();

        $pedido = $referencia['pedido'] ?? null;
        $montado = $referencia['montado'] ?? null;
        $publicado = $referencia['publicado'] ?? null;
        $hayPublico = $hitos->contains('publico', true);
        // Publico hoy pero sin fecha anotada (de antes de que se anotara):
        // se dice, no se inventa.
        $publicadoSinFecha = $hayPublico && ! $publicado;

        $leads = Inquiry::whereIn('project_id', $ids)->get(['created_at', 'estado', 'estado_en', 'contestado_en']);
        $contestados = $leads->filter(fn ($l) => $l->estado !== null && $l->estado !== 'nuevo');
        // La primera respuesta, no el ultimo cambio de estado: cerrar un lead
        // una semana despues no lo saca de "en el dia".
        $enElDia = $contestados->filter(function ($l) {
            $primera = $l->contestado_en ?? $l->estado_en;

            return $primera && $l->created_at->diffInHours($primera, false) <= self::HORAS_PARA_CONTESTAR;
        });

        // La etapa mira todos los proyectos (el paso mas avanzado que haya
        // hoy), no solo el de referencia.
        [$etapa, $desde] = self::etapa([
            'proyectos' => $proyectos->count(), 'alta' => $promotora->created_at, 'primer_proyecto' => self::fecha($proyectos->min('created_at')),
            'unidades' => self::fecha(Unit::whereIn('project_id', $ids)->max('created_at')),
            'material' => self::fecha(MaterialDelProyecto::whereIn('project_id', $ids)->max('created_at')),
            'pedido' => $hitos->pluck('pedido')->filter()->max(),
            'montado' => $hitos->pluck('montado')->filter()->max(),
            'publico' => $hayPublico, 'publicado' => $publicado,
            'leads' => $leads->count(), 'ultimo_lead' => self::fecha($leads->max('created_at')),
        ]);

        return [
            'promotora' => $promotora,
            'plan' => $promotora->companyProfile?->plan_tier,
            'alta' => $promotora->created_at,
            'proyectos' => $proyectos->count(),
            'proyecto' => $referencia['proyecto'] ?? null,
            'visor_pedido' => $pedido,
            'visor_montado' => $montado,
            'publicado' => $publicado,
            'publicado_sin_fecha' => $publicadoSinFecha,
            // Sin signo: si el equipo pidio el visor antes de dar de alta a la
            // promotora (pasa en pilotos), son cero dias, no dias negativos.
            'dias_alta_a_pedido' => $pedido ? max(0, (int) $promotora->created_at->diffInDays($pedido, false)) : null,
            'dias_pedido_a_publicado' => ($pedido && $publicado) ? max(0, (int) $pedido->diffInDays($publicado, false)) : null,
            'leads' => $leads->count(),
            'leads_semana' => $leads->filter(fn ($l) => $l->created_at->gte(now()->subDays(7)))->count(),
            'leads_contestados' => $contestados->count(),
            'leads_en_el_dia' => $enElDia->count(),
            'visitas_30d' => $ids->isEmpty() ? 0 : ResumenDeTreintaDias::de($ids)['visitas'],
            'etapa' => $etapa,
            'etapa_desde' => $desde,
            'dias_en_etapa' => $desde ? max(0, (int) $desde->diffInDays(now(), false)) : null,
        ];
    }

    /**
     * En que paso esta hoy (o se ha quedado), y desde cuando. Del final al
     * principio: el paso mas avanzado que haya manda, y "publico" es publico
     * hoy, no publicado alguna vez (despublicar vuelve atras).
     */
    private static function etapa(array $d): array
    {
        if ($d['publico']) {
            return $d['leads'] === 0 ? ['sin_leads', $d['publicado']] : ['en_marcha', $d['ultimo_lead']];
        }
        if ($d['montado']) {
            return ['sin_publicar', $d['montado']];
        }
        if ($d['pedido']) {
            return ['esperando_equipo', $d['pedido']];
        }
        if ($d['material']) {
            return ['sin_pedir_visor', $d['material']];
        }
        if ($d['unidades']) {
            return ['sin_material', $d['unidades']];
        }
        if ($d['proyectos'] > 0) {
            return ['sin_viviendas', $d['primer_proyecto']];
        }

        return ['sin_proyecto', $d['alta']];
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
