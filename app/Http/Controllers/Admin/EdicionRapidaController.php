<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Viviendas\EdicionRapida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * La tabla de viviendas con precio y estado editables y cambios en lote.
 */
class EdicionRapidaController extends Controller
{
    public function guardar(Request $request, Project $project)
    {
        $usuario = $request->user();
        abort_unless($usuario->canAccessProject($project), 403);
        // Quien edita lo comercial, y el agente, que solo reserva (y eso lo
        // juzga cada fila).
        abort_unless($usuario->isAgente() || Gate::allows('edit-unit-commercial', $project), 403);

        $datos = $request->validate([
            'v' => 'nullable|array',
            'v.*' => 'array',
            'sel' => 'nullable|array',
            'sel.*' => 'integer',
            'lote_accion' => 'nullable|in:estado,porcentaje',
            'lote_estado' => 'required_if:lote_accion,estado|nullable|in:available,reserved,sold',
            'lote_porcentaje' => 'required_if:lote_accion,porcentaje|nullable|numeric|min:-90|max:300',
        ], [
            'lote_estado.required_if' => 'Elige el estado que quieres poner.',
            'lote_porcentaje.required_if' => 'Escribe el porcentaje a aplicar.',
            'lote_porcentaje.min' => 'Bajar más de un 90% deja el precio en casi nada; revisa el porcentaje.',
            'lote_porcentaje.max' => 'Subir más de un 300% parece un error; revisa el porcentaje.',
        ]);

        $accion = $datos['lote_accion'] ?? null;
        $marcadas = $datos['sel'] ?? [];
        // Antes de guardar nada: si el lote no vale se vuelve con lo editado
        // en el formulario, no con la mitad ya en la base de datos.
        if ($accion && ! $marcadas) {
            return back()->withInput()->withErrors(['sel' => 'Marca las viviendas a las que aplicar el cambio.']);
        }

        $edicion = new EdicionRapida($project, $usuario);
        $edicion->filas($datos['v'] ?? []);
        if ($accion === 'estado') {
            $edicion->estadoEnLote($marcadas, $datos['lote_estado']);
        } elseif ($accion === 'porcentaje') {
            $edicion->porcentajeEnLote($marcadas, (float) $datos['lote_porcentaje']);
        }

        return redirect()->route('admin.projects.units.index', $project)->with('edicion_rapida', $edicion->resumen());
    }
}
