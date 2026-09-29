<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Pilotos\Embudo;
use Illuminate\Http\Request;

/** El embudo de cada piloto, para el equipo. */
class PilotosController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR), 403);

        $embudos = Embudo::deTodas();

        // Las medias solo de quien llego a ese paso: una promotora que no ha
        // pedido visor no baja la media de los que si.
        $media = function (string $clave) use ($embudos): ?float {
            $valores = array_values(array_filter(array_column($embudos, $clave), fn ($v) => $v !== null));

            return $valores ? round(array_sum($valores) / count($valores), 1) : null;
        };

        return view('admin.pilotos', [
            'embudos' => $embudos,
            'medias' => [
                'alta_a_pedido' => $media('dias_alta_a_pedido'),
                'pedido_a_publicado' => $media('dias_pedido_a_publicado'),
                'leads_semana' => array_sum(array_column($embudos, 'leads_semana')),
                'contestados' => array_sum(array_column($embudos, 'leads_contestados')),
                'en_el_dia' => array_sum(array_column($embudos, 'leads_en_el_dia')),
                'leads' => array_sum(array_column($embudos, 'leads')),
            ],
            'porEtapa' => array_count_values(array_column($embudos, 'etapa')),
        ]);
    }
}
