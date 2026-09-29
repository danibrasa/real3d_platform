<?php

namespace App\Support\Facturacion;

use App\Models\Project;

/**
 * Si el chatbot de un proyecto esta abierto al publico, y si no, por que.
 *
 * Tres llaves y hacen falta las tres: que el chatbot exista en esta
 * instalacion, que la promotora lo tenga encendido en el proyecto, y que su
 * plan lo incluya. La tercera faltaba. El widget se pintaba y la API
 * contestaba para cualquier proyecto publicado, tambien los del plan
 * gratuito, y cada respuesta la paga esta casa al proveedor de IA. Era el
 * visor otra vez -- lo que separa el plan gratuito del de pago, regalado --
 * pero con factura.
 *
 * Las tres llaves viven aqui y en ningun otro sitio. El widget y la API
 * miraban cada uno las suyas, y asi es como una puerta se queda sin la
 * tercera llave sin que la otra se entere.
 */
class AccesoAlChatbot
{
    /** Por que no esta abierto, en las palabras que devuelve la API; null si lo esta. */
    public static function porQueNo(Project $proyecto): ?string
    {
        if (! config('chatbot.enabled')) {
            return 'Chatbot is not enabled.';
        }

        if (! $proyecto->chatbot_enabled) {
            return 'Chatbot is not enabled for this project.';
        }

        if (! PlanDelProyecto::incluye($proyecto, 'chatbot')) {
            return 'Chatbot is not included in the project plan.';
        }

        return null;
    }

    public static function abierto(Project $proyecto): bool
    {
        return self::porQueNo($proyecto) === null;
    }
}
