<?php

namespace App\Support\Publicacion;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Por donde va una promotora nueva, y que le toca hacer ahora.
 *
 * Nace de mirar lo que ve alguien recien registrado al entrar: un menu y nada
 * mas. Ni un boton, ni una indicacion. Tenia que adivinar sola que lo primero
 * es ir a "Proyectos" y crear uno, y eso en un producto que promete que poner tu
 * proyecto en marcha cuesta poco trabajo.
 *
 * Los pasos son los del reparto acordado: ella crea el proyecto y carga sus
 * viviendas, el equipo monta el 3D, y ella publica.
 */
class PrimerosPasos
{
    public function __construct(private User $usuario) {}

    public static function de(User $usuario): self
    {
        return new self($usuario);
    }

    /** Solo se enseña mientras haya algo que hacer, y solo a las promotoras. */
    public function hayQueEnseñarlos(): bool
    {
        return $this->usuario->isInmobiliaria() && ! $this->terminado();
    }

    public function terminado(): bool
    {
        return $this->pasos()->every(fn ($p) => $p['hecho']);
    }

    /**
     * @return Collection<int, array{
     *     clave: string, hecho: bool, actual: bool, enlace: ?string, de: string
     * }>
     */
    public function pasos()
    {
        $proyecto = $this->primerProyecto();
        $conViviendas = $proyecto && $proyecto->units()->exists();
        $lista = $proyecto ? ListaParaPublicar::de($proyecto) : null;

        $conVisor = $lista && $lista->puedePublicarse();
        $pedido = $proyecto && $proyecto->viewer_requested_at !== null;
        $publicado = $proyecto && in_array($proyecto->status, ListaParaPublicar::VISIBLES, true);

        $pasos = [
            [
                'clave' => 'crear_proyecto',
                'hecho' => (bool) $proyecto,
                'de' => ListaParaPublicar::PROMOTORA,
                'enlace' => $proyecto ? null : route('admin.projects.create'),
            ],
            [
                'clave' => 'cargar_viviendas',
                'hecho' => $conViviendas,
                'de' => ListaParaPublicar::PROMOTORA,
                'enlace' => $proyecto && ! $conViviendas
                    ? route('admin.projects.units.import.create', $proyecto)
                    : null,
            ],
            [
                // Este no lo hace ella: solo avisa. Se marca hecho en cuanto lo
                // ha pedido, para que no parezca que sigue pendiente de algo suyo.
                'clave' => 'pedir_visor',
                'hecho' => $conVisor || $pedido,
                'de' => ListaParaPublicar::EQUIPO,
                'enlace' => $conViviendas && ! $conVisor && ! $pedido && $proyecto
                    ? route('admin.projects.edit', $proyecto)
                    : null,
            ],
            [
                'clave' => 'publicar',
                'hecho' => $publicado,
                'de' => ListaParaPublicar::PROMOTORA,
                'enlace' => $conVisor && ! $publicado && $proyecto
                    ? route('admin.projects.edit', $proyecto)
                    : null,
            ],
        ];

        // El primero sin hacer es el que toca: se marca para que salte a la
        // vista cual es la siguiente accion y no haya que leerse la lista.
        $siguiente = collect($pasos)->search(fn ($p) => ! $p['hecho']);

        return collect($pasos)->map(fn ($p, $i) => $p + ['actual' => $i === $siguiente]);
    }

    private function primerProyecto(): ?Project
    {
        return $this->usuario->accessibleProjects()->oldest('id')->first();
    }
}
