<?php

namespace App\Support\Publicacion;

use App\Models\Project;

/**
 * Que le falta a un proyecto para salir a la web.
 *
 * Nace de un recorrido de alta hecho como si fuera una promotora nueva: se podia
 * crear el proyecto, importar las viviendas, poner el estado en "publico"... y
 * quedarse en borrador sin que nada lo dijera. Y aunque se publicara, sin
 * coordenadas no aparece en el portal, tambien en silencio.
 *
 * Asi que esto hace dos cosas: impedir que se publique lo que no tiene nada que
 * enseñar, y avisar de lo que se puede publicar pero saldra a medias. Cada punto
 * dice ademas de quien es, porque en el reparto acordado el montaje 3D lo hace
 * el equipo de Real3D y lo comercial la promotora: que a nadie le pidan algo que
 * no puede hacer.
 */
class ListaParaPublicar
{
    /** Estados en los que el proyecto se ve desde fuera. */
    public const VISIBLES = ['public', 'unlisted'];

    /** De quien es cada cosa que falta. */
    public const EQUIPO = 'equipo';

    public const PROMOTORA = 'promotora';

    public function __construct(private Project $proyecto) {}

    public static function de(Project $proyecto): self
    {
        return new self($proyecto);
    }

    /**
     * Lo que impide publicar: sin esto no hay literalmente nada que enseñar.
     *
     * @return array<int, array{clave: string, de: string}>
     */
    public function bloqueos(): array
    {
        $fuera = [];

        if (! $this->tieneModelo() && ! $this->tieneFondo()) {
            $fuera[] = ['clave' => 'sin_visor', 'de' => self::EQUIPO];
        }

        return $fuera;
    }

    /**
     * Lo que se puede publicar, pero conviene saber antes.
     *
     * @return array<int, array{clave: string, de: string}>
     */
    public function avisos(): array
    {
        $fuera = [];

        if ($this->tieneFondo() && ! $this->tieneModelo()) {
            $fuera[] = ['clave' => 'sin_modelo', 'de' => self::EQUIPO];
        }

        if ($this->tieneModelo() && ! $this->tieneFondo()) {
            $fuera[] = ['clave' => 'sin_fondo', 'de' => self::EQUIPO];
        }

        // El fallo mudo que mas cuesta encontrar: el proyecto esta publico, se
        // ve en el listado, y no aparece en el portal porque scopePortalVisible
        // exige coordenadas.
        if ($this->proyecto->latitude === null || $this->proyecto->longitude === null) {
            $fuera[] = ['clave' => 'sin_coordenadas', 'de' => self::PROMOTORA];
        }

        if ($this->proyecto->units()->count() === 0) {
            $fuera[] = ['clave' => 'sin_viviendas', 'de' => self::PROMOTORA];
        }

        if (! $this->proyecto->contact_email) {
            $fuera[] = ['clave' => 'sin_contacto', 'de' => self::PROMOTORA];
        }

        if (! $this->tieneFichero('thumbnail')) {
            $fuera[] = ['clave' => 'sin_portada', 'de' => self::EQUIPO];
        }

        return $fuera;
    }

    public function puedePublicarse(): bool
    {
        return $this->bloqueos() === [];
    }

    /** True si ese cambio de estado saca el proyecto a la web. */
    public static function esSalirALaWeb(string $nuevo, ?string $anterior): bool
    {
        return in_array($nuevo, self::VISIBLES, true)
            && ! in_array($anterior, self::VISIBLES, true);
    }

    private function tieneModelo(): bool
    {
        return $this->tieneFichero('model_3d');
    }

    private function tieneFondo(): bool
    {
        return $this->tieneFichero('video_360') || $this->tieneFichero('image_360');
    }

    /** Solo cuentan las subidas terminadas: los videos 360 van por trozos. */
    private function tieneFichero(string $tipo): bool
    {
        return $this->proyecto->files()
            ->where('file_type', $tipo)
            ->where('upload_complete', true)
            ->exists();
    }
}
