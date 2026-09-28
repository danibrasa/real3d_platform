<?php

namespace App\Support\Facturacion;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;

/**
 * Que incluye el plan de la promotora de un proyecto.
 *
 * Lo que hay en PLAN_LIMITS se pregunta normalmente por usuario -- "esta
 * promotora tiene chatbot?" -- pero hay cosas que se sirven al publico sin
 * sesion: el visor 3D y el widget incrustable. Ahi no hay usuario a quien
 * preguntar, solo un proyecto, y hay que llegar a su promotora.
 *
 * Vive aparte porque ya lo necesitan dos sitios, y porque la parte delicada es
 * la misma en los dos: que hacer cuando no hay promotora.
 */
class PlanDelProyecto
{
    /**
     * Si el plan de la promotora de este proyecto incluye esa prestacion.
     *
     * Un proyecto sin promotora detras la incluye. No es un cliente que se
     * haya dado de baja: es un proyecto nuestro -- una demo, la portada -- y
     * no hay suscripcion que pueda caducar. Cerrarlo romperia la portada.
     *
     * Con varias promotoras asignadas basta con que una la tenga: el proyecto
     * es de todas, y quitarselo a una por el plan de otra seria cobrarle a
     * quien esta al corriente.
     */
    public static function incluye(Project $proyecto, string $prestacion): bool
    {
        $perfiles = self::perfiles($proyecto);

        if ($perfiles->isEmpty()) {
            return true;
        }

        return $perfiles->contains(
            fn (CompanyProfile $perfil) => (bool) ($perfil->getPlanLimits()[$prestacion] ?? false)
        );
    }

    /**
     * Un tope numerico del plan de la promotora de este proyecto.
     *
     * Se pregunta por el proyecto y no por quien hace la peticion. Con el
     * perfil de quien pide, el tope se saltaba por dos caminos: un usuario sin
     * ficha de empresa -el equipo, por ejemplo- no tiene plan que mirar, asi
     * que anadiendo viviendas en nombre de una promotora del plan gratuito no
     * se le aplicaba el suyo.
     *
     * Sin promotora detras no hay tope: es un proyecto nuestro.
     */
    public static function limite(Project $proyecto, string $clave, int $porDefecto = PHP_INT_MAX): int
    {
        $perfiles = self::perfiles($proyecto);

        if ($perfiles->isEmpty()) {
            return $porDefecto;
        }

        // Con varias promotoras vale el mas alto, por el mismo motivo que en
        // incluye(): no castigar a la que esta al corriente.
        return (int) $perfiles
            ->map(fn (CompanyProfile $perfil) => $perfil->getPlanLimits()[$clave] ?? 0)
            ->max();
    }

    /** Las fichas de empresa de las promotoras asignadas al proyecto. */
    private static function perfiles(Project $proyecto)
    {
        return $proyecto->assignedAgencies
            ->map(fn (User $u) => $u->companyProfile)
            ->filter();
    }
}
