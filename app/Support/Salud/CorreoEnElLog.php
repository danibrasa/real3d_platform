<?php

namespace App\Support\Salud;

/**
 * Leer lo que el mailer 'log' escribe, mensaje a mensaje.
 *
 * El fichero es una tira de mensajes MIME, cada uno detras de una cabecera
 * de log. Buscar el destinatario y la marca del aviso por separado en todo
 * el fichero dejaba pasar lo que la marca existe para impedir: los dos
 * caminos del lead avisan a la misma promotora, y un fichero con el aviso
 * del formulario (a ella) y cualquier otra cosa con la marca del chatbot
 * daba por bueno un aviso del chatbot que podia haber ido a otro sitio, o a
 * ninguno. Aqui las dos condiciones se exigen sobre el mismo mensaje.
 *
 * Vive en la aplicacion y no en el guion que lo usa para poder probarlo con
 * un correo de verdad, escrito por el mailer de verdad, y no con un fichero
 * que se parezca a lo que uno cree que escribe.
 */
class CorreoEnElLog
{
    /** Cada entrada del log empieza asi; lo que hay entre dos es un mensaje. */
    private const CABECERA = '/^\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d(?:\.\d+)?\] \w+\.[A-Z]+: /m';

    /**
     * @return string[] los mensajes, con las cabeceras MIME desplegadas
     */
    public static function mensajes(string $contenido): array
    {
        $trozos = preg_split(self::CABECERA, $contenido);

        return array_values(array_filter(array_map(
            // Una cabecera larga se parte en varias lineas con un espacio
            // delante (RFC 5322). Con un nombre de proyecto largo, el asunto
            // se partia por la mitad y la marca dejaba de encontrarse.
            fn (string $m) => preg_replace('/\r?\n[ \t]+/', '', trim($m)),
            $trozos
        ), fn (string $m) => $m !== ''));
    }

    /**
     * Si hay un mensaje dirigido a ese correo que lleve ademas la marca.
     *
     * La marca es un texto que solo lleva el aviso que se busca, como el
     * asunto con el nombre del comprador. Vacia, basta con el destinatario.
     */
    public static function hayAvisoPara(string $contenido, string $destinatario, string $marca = ''): bool
    {
        foreach (self::mensajes($contenido) as $mensaje) {
            if (! preg_match('/^To:.*'.preg_quote($destinatario, '/').'/mi', $mensaje)) {
                continue;
            }

            if ($marca === '' || stripos($mensaje, $marca) !== false) {
                return true;
            }
        }

        return false;
    }
}
