<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConversation;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lo que una cuenta puede llevarse: sus datos, en un fichero.
 *
 * Es un derecho (portabilidad, RGPD y Ley 172-13) y es lo que la politica de
 * privacidad promete que se puede hacer desde el panel. Sale en JSON y no en
 * PDF: es para llevarselo a otro sitio, no para leerlo en el sofa.
 *
 * Solo lo suyo: la ficha de empresa, sus proyectos con viviendas y ficheros
 * (los nombres, no los ficheros), las consultas de compradores que ha
 * recibido y las conversaciones del asistente de sus proyectos. Nada de
 * otras promotoras, aunque compartan proyecto.
 */
class DatosPersonalesController extends Controller
{
    public function exportar(Request $request): StreamedResponse
    {
        $usuario = $request->user();
        $proyectos = $usuario->assignedProjects()
            ->with(['units', 'files', 'settings'])
            ->get();

        $ids = $proyectos->pluck('id');

        $datos = [
            'exportado_en' => now()->toIso8601String(),
            'cuenta' => [
                'nombre' => $usuario->name,
                'correo' => $usuario->email,
                'rol' => $usuario->role,
                'creada_en' => $usuario->created_at?->toIso8601String(),
                'condiciones_aceptadas_en' => $usuario->legal_aceptado_en?->toIso8601String(),
                'version_de_las_condiciones' => $usuario->legal_version,
            ],
            'empresa' => $usuario->companyProfile?->toArray(),
            'proyectos' => $proyectos->map(fn ($p) => [
                'proyecto' => $p->only(['id', 'name', 'slug', 'status', 'location', 'description', 'created_at']),
                'viviendas' => $p->units->toArray(),
                'ficheros' => $p->files->map(fn ($f) => $f->only(['file_type', 'original_name', 'file_size', 'created_at']))->all(),
                'ajustes' => $p->settings?->toArray(),
            ])->all(),
            'consultas' => Inquiry::whereIn('project_id', $ids)->get()->toArray(),
            'conversaciones' => ChatbotConversation::whereIn('project_id', $ids)
                ->with('messages')
                ->get()
                ->toArray(),
        ];

        $nombre = 'real3d-datos-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($datos) {
            echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $nombre, ['Content-Type' => 'application/json']);
    }
}
