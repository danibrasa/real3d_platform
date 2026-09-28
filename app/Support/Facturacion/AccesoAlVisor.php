<?php

namespace App\Support\Facturacion;

use App\Models\Project;
use App\Models\User;

/**
 * Quien puede pedir que le montemos el visor 3D.
 *
 * Es la unica cosa del producto que cuesta dinero hacer: el modelo y el fondo
 * los monta el equipo, uno a uno, y esa es la razon de que haya un plan de
 * pago. Hasta ahora no lo comprobaba nadie. Una promotora en el plan gratuito
 * pedia su visor, el equipo recibia el aviso, se lo montaba y publicaba: el
 * plan gratuito daba exactamente lo mismo que el de pago y nada en el codigo
 * decia lo contrario.
 *
 * Lo que se mira es el plan, no la suscripcion de Stripe. Asi un superadmin
 * puede concederselo a alguien a mano -- una prueba, un acuerdo, una
 * promotora invitada -- sin tener que pasar por la pasarela, y el entorno de
 * desarrollo puede recorrer el circuito entero sin Stripe montado.
 */
class AccesoAlVisor
{
    /** El equipo monta visores; no tiene que comprarlos. */
    public static function esDelEquipo(User $usuario): bool
    {
        return $usuario->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
    }

    /**
     * Si el visor 3D de este proyecto se sigue sirviendo al publico.
     *
     * Cerrar quien puede PEDIR un visor no bastaba. Una vez montado y
     * publicado, lo publico solo miraba el estado del proyecto: se podia
     * contratar, esperar a que lo montaramos, darse de baja, y quedarselo
     * funcionando para siempre. Con la prueba de catorce dias, ni siquiera
     * hacia falta llegar a pagar una factura.
     *
     * Lo que se pierde al darse de baja es solo el visor. La pagina sigue
     * publicada, con sus viviendas y recibiendo contactos, que es exactamente
     * el plan gratuito: se pierde lo que se dejo de pagar y nada mas.
     *
     * Un proyecto sin promotora detras si se sirve. No es un cliente que se
     * haya dado de baja: es un proyecto nuestro -- una demo, el portal -- y no
     * hay suscripcion que pueda caducar. Cerrarlo romperia la portada.
     */
    public static function servidoEnPublico(Project $proyecto): bool
    {
        // Se pregunta por la prestacion y no por el nombre del plan. Con
        // "distinto de starter" el visor quedaba fuera de la tabla de limites,
        // y por eso la auditoria no lo cubrio: era el unico que se comprobaba
        // a mano y fue el unico que estuvo meses sin puerta.
        return PlanDelProyecto::incluye($proyecto, 'visor_3d');
    }

    public static function puedePedirlo(?User $usuario): bool
    {
        if (! $usuario) {
            return false;
        }

        if (self::esDelEquipo($usuario)) {
            return true;
        }

        // Sin ficha de empresa no hay plan que mirar, y sin plan no se pide.
        return (bool) $usuario->companyProfile?->hasFeature('visor_3d');
    }
}
