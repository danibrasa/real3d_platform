<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SolicitudDeVisor;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use App\Support\Publicacion\ListaParaPublicar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

        if ($project->viewer_requested_at) {
            return back()->with('info', __('visor.ya_pedido'));
        }

        $project->update([
            'viewer_requested_at' => now(),
            'viewer_requested_by' => $user->id,
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

        $project->update(['viewer_requested_at' => null, 'viewer_requested_by' => null]);

        AuditLog::record('viewer_request_withdrawn', $project);

        return back()->with('success', __('visor.retirado'));
    }

    /**
     * La cola del equipo: que proyectos esperan visor, el que mas lleva primero.
     *
     * Es la otra mitad de la costura. Sin esta pantalla las solicitudes viven
     * solo en un correo, y un correo se pierde.
     */
    public function pendientes(Request $request)
    {
        abort_unless($request->user()->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR), 403);

        $proyectos = Project::whereNotNull('viewer_requested_at')
            ->with(['assignedAgencies.companyProfile', 'solicitanteDelVisor'])
            ->withCount('units')
            ->orderBy('viewer_requested_at')
            ->paginate(25);

        // Lo que falta en cada uno, para poder decir de un vistazo si ya se
        // puede publicar en cuanto se suba el fondo.
        $listas = $proyectos->mapWithKeys(
            fn ($p) => [$p->id => ListaParaPublicar::de($p)]
        );

        return view('admin.visores-pendientes', compact('proyectos', 'listas'));
    }
}
