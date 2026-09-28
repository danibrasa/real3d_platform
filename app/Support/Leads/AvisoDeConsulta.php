<?php

namespace App\Support\Leads;

use App\Mail\InquiryAutoReply;
use App\Mail\NewInquiryNotification;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Avisar de un lead: a quien le interesa, y al que pregunta.
 *
 * Vive aparte porque hay dos formas de dejar los datos -- el formulario de la
 * ficha y el chatbot -- y solo una avisaba. El chatbot guardaba la consulta en
 * la base y no mandaba nada: la promotora tenia el lead delante y no lo sabia.
 * Es el mismo fallo que ya costo semanas por el otro camino, repetido en este.
 *
 * Con la logica en un solo sitio, la siguiente forma de capturar un lead
 * -- un formulario nuevo, una landing, lo que sea -- no puede olvidarse de
 * avisar sin borrar una linea a proposito.
 */
class AvisoDeConsulta
{
    /**
     * Manda los avisos y devuelve a cuanta gente se aviso.
     *
     * Devuelve el numero y no void para que quien llame pueda dejarlo en el
     * registro: "se creo el lead" y "se aviso a tres personas" son dos hechos
     * distintos, y confundirlos es como se pierde un lead sin ruido.
     */
    public static function enviar(Inquiry $consulta, Project $proyecto): int
    {
        $destinatarios = self::destinatarios($proyecto);

        foreach ($destinatarios as $correo) {
            Mail::to($correo)->queue(new NewInquiryNotification($consulta));
        }

        // Y al comprador, que acaba de dejar sus datos y merece saber que
        // llegaron.
        Mail::to($consulta->email)->queue(new InquiryAutoReply($consulta));

        return $destinatarios->count();
    }

    /**
     * Quien tiene que enterarse de este lead.
     *
     * @return Collection<int, string>
     */
    public static function destinatarios(Project $proyecto): Collection
    {
        $destinatarios = collect();

        if ($proyecto->contact_email) {
            $destinatarios->push($proyecto->contact_email);
        } else {
            // Sin correo de contacto configurado, el aviso solo iba a los
            // superadmin del SaaS: la promotora dueña del proyecto no se
            // enteraba de su propio lead, y rellenar ese campo es justo lo que
            // se olvida al dar de alta un proyecto.
            $destinatarios = $destinatarios->merge($proyecto->assignedAgencies()->pluck('email'));

            if ($destinatarios->isEmpty() && $proyecto->creator) {
                $destinatarios->push($proyecto->creator->email);
            }
        }

        return $destinatarios
            ->merge(User::where('role', User::ROLE_SUPERADMIN)->pluck('email'))
            ->unique()
            ->values();
    }
}
