<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Las tres paginas legales: privacidad, condiciones y cookies.
 *
 * Una por idioma, en resources/views/legal/{es,en}/. Se escogen por el
 * idioma de la peticion y no por el de la cuenta: quien las lee suele no
 * tener cuenta todavia, que es justo cuando mas importan.
 */
class LegalController extends Controller
{
    public const PAGINAS = ['privacidad', 'condiciones', 'cookies'];

    public function __invoke(string $pagina): View
    {
        abort_unless(in_array($pagina, self::PAGINAS, true), 404);

        $idioma = in_array(app()->getLocale(), ['es', 'en'], true) ? app()->getLocale() : 'es';

        return view('legal.pagina', [
            'pagina' => $pagina,
            'cuerpo' => "legal.{$idioma}.{$pagina}",
            'titulo' => __("legal.{$pagina}"),
        ]);
    }
}
