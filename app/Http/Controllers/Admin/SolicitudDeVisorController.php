<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SolicitudDeVisor;
use App\Mail\VisorMontado;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use App\Support\Facturacion\AccesoAlVisor;
use App\Support\Facturacion\PruebaGratuita;
use App\Support\Publicacion\ListaParaPublicar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * La promotora avisa de que su proyecto esta listo para que le monten el visor.
 *
 * Es la costura del reparto acordado: ella carga sus viviendas y sus precios, el
 * equipo monta el modelo 3D y el fondo 360. Sin esto, la promotora terminaba su
 * parte y se quedaba delante de un aviso que decia "lo hace el equipo de Real3D"
 * sin ningun boton, y el equipo no tenia forma de saber a quien le toca.
 */
class SolicitudDeVisorController extends Controller
{
    /** La promotora pide el visor. */
    public function pedir(Request $request, Project $project)
    {
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        // El visor es lo unico que cuesta dinero hacer: lo monta el equipo, uno
        // a uno. Era tambien lo unico que separaba el plan gratuito del de pago
        // y no lo comprobaba nadie, asi que en la practica no lo separaba nada.
        //
        // No es un 403 sino un aviso con salida: quien llega aqui no esta
        // haciendo nada raro, le falta contratar, y la pantalla de al lado se
        // lo resuelve.
        if (! AccesoAlVisor::puedePedirlo($user)) {
            return back()->with('error', __('visor.hace_falta_plan'));
        }

        if ($project->viewer_requested_at) {
            return back()->with('info', __('visor.ya_pedido'));
        }

        $project->update([
            'viewer_requested_at' => now(),
            'viewer_requested_by' => $user->id,
            'visor_estado' => Project::VISOR_PEDIDO,
            'visor_estado_en' => now(),
        ]);

        AuditLog::record('viewer_requested', $project, null, [
            'proyecto' => $project->name,
            'viviendas' => $project->units()->count(),
        ]);

        // A quien lo monta. Si no hubiera nadie con ese papel el aviso se
        // perderia, asi que se deja constancia en el registro igualmente.
        $equipo = User::whereIn('role', [User::ROLE_SUPERADMIN, User::ROLE_GESTOR])
            ->pluck('email');

        foreach ($equipo as $correo) {
            Mail::to($correo)->queue(new SolicitudDeVisor($project, $user));
        }

        return back()->with('success', __('visor.pedido'));
    }

    /** El equipo cancela la solicitud (o la promotora se arrepiente). */
    public function retirar(Request $request, Project $project)
    {
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        $project->update(['viewer_requested_at' => null, 'viewer_requested_by' => null, 'visor_estado' => null, 'visor_estado_en' => null]);

        AuditLog::record('viewer_request_withdrawn', $project);

        return back()->with('success', __('visor.retirado'));
    }

    /**
     * El equipo da por montado el visor y avisa a quien lo pedia.
     *
     * Sin esto la solicitud se quedaba en la cola para siempre: el equipo subia
     * el modelo y nadie cerraba el circulo, asi que la promotora no sabia que ya
     * podia publicar y el equipo veia una lista que solo crecia.
     */
    public function marcarMontado(Request $request, Project $project)
    {
        $user = $request->user();

        abort_unless($user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR), 403);

        if (! $project->viewer_requested_at) {
            return back()->with('info', __('visor.no_estaba_pedido'));
        }

        // No se da por montado lo que no lo esta: si no hay ni modelo ni fondo,
        // avisar a la promotora de que ya puede publicar seria mandarla a una
        // pagina vacia con su nombre encima.
        if (! ListaParaPublicar::de($project)->puedePublicarse()) {
            return back()->with('error', __('visor.aun_no_hay_visor'));
        }

        $quienLoPidio = $project->solicitanteDelVisor;

        $project->update([
            'viewer_requested_at' => null,
            'viewer_requested_by' => null,
            'visor_estado' => Project::VISOR_MONTADO,
            'visor_estado_en' => now(),
        ]);

        // Aqui empieza a contar la prueba, que es el primer momento en que hay
        // algo que probar. Contandola desde el pago, la promotora se gastaba
        // los dias esperando a que le montaramos el visor.
        //
        // Si Stripe no contesta no se bloquea nada de lo de arriba: el visor
        // esta montado igual y la promotora tiene que enterarse. Queda sin
        // anclar, que es recuperable, en vez de quedarse sin aviso.
        $finDePrueba = null;

        try {
            $finDePrueba = PruebaGratuita::anclarAlMontarVisor($quienLoPidio);
        } catch (\Throwable $e) {
            report($e);
        }

        AuditLog::record('viewer_ready', $project, null, [
            'proyecto' => $project->name,
            'montado_por' => $user->email,
            'prueba_hasta' => $finDePrueba?->toDateString(),
        ]);

        if ($quienLoPidio) {
            Mail::to($quienLoPidio->email)->queue(new VisorMontado($project, $user));
        }

        return back()->with('success', __('visor.marcado_montado', ['proyecto' => $project->name]));
    }

    /**
     * La cola del equipo: que proyectos esperan visor, el que mas lleva primero.
     *
     * Es la otra mitad de la costura. Sin esta pantalla las solicitudes viven
     * solo en un correo, y un correo se pierde.
     */
    /**
     * La cola de trabajo: en que va cada visor, quien lo lleva, para cuando y
     * cuantas horas. Cambiar de estado reinicia el reloj de "parado"; lo
     * demas no. A "montado" no se llega por aqui sino dandolo por montado,
     * que comprueba que hay algo montado.
     */
    public function actualizar(Request $request, Project $project)
    {
        abort_unless($request->user()->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR), 403);

        $validated = $request->validate([
            'estado' => ['required', Rule::in(array_diff(Project::ESTADOS_VISOR, [Project::VISOR_MONTADO]))],
            'asignado_a' => ['nullable', Rule::exists('users', 'id')->whereIn('role', [User::ROLE_SUPERADMIN, User::ROLE_GESTOR])],
            'objetivo' => ['nullable', 'date'],
            'horas' => ['nullable', 'numeric', 'min:0', 'max:999'],
        ]);

        $cambios = [
            'visor_asignado_a' => $validated['asignado_a'] ?? null,
            'visor_objetivo' => $validated['objetivo'] ?? null,
            'visor_horas' => $validated['horas'] ?? null,
        ];
        if ($validated['estado'] !== $project->visor_estado) {
            $cambios['visor_estado'] = $validated['estado'];
            $cambios['visor_estado_en'] = now();
        }

        $antes = $project->only(['visor_estado', 'visor_asignado_a', 'visor_objetivo', 'visor_horas']);
        $project->update($cambios);
        AuditLog::record('viewer_queue_updated', $project, $antes, $cambios);

        return back()->with('success', __('visor.cola_guardada', ['proyecto' => $project->name]));
    }

    public function pendientes(Request $request)
    {
        abort_unless($request->user()->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR), 403);

        // Primero lo que mas lleva sin moverse, no lo que mas lleva pedido:
        // un visor en revision desde ayer va detras de uno pedido hace una
        // semana que nadie ha cogido.
        $proyectos = Project::whereNotNull('viewer_requested_at')
            ->with(['assignedAgencies.companyProfile', 'solicitanteDelVisor', 'montador'])
            ->withCount(['units', 'material'])
            ->orderByRaw('coalesce(visor_estado_en, viewer_requested_at)')
            ->paginate(25);

        $equipo = User::whereIn('role', [User::ROLE_SUPERADMIN, User::ROLE_GESTOR])->orderBy('name')->get(['id', 'name']);

        // Lo que falta en cada uno, para poder decir de un vistazo si ya se
        // puede publicar en cuanto se suba el fondo.
        $listas = $proyectos->mapWithKeys(
            fn ($p) => [$p->id => ListaParaPublicar::de($p)]
        );

        return view('admin.visores-pendientes', compact('proyectos', 'listas', 'equipo'));
    }
}
