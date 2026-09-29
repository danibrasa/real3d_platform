<?php

/*
|--------------------------------------------------------------------------
| Quien responde del servicio, para las paginas legales
|--------------------------------------------------------------------------
|
| Lo que cambia de una empresa a otra vive aqui y no dentro de los textos:
| los textos son largos, estan en dos idiomas, y un dato repetido en seis
| sitios se corrige en cinco. El domicilio y el registro se rellenan en el
| .env de cada entorno cuando se tengan; mientras, las paginas los omiten
| en vez de enseñar un hueco.
|
*/

return [

    'responsable' => env('LEGAL_RESPONSABLE', 'XTUDIO NETWORKS'),
    'marca' => 'Real3D.io',
    'correo' => env('LEGAL_EMAIL', 'legal@real3d.io'),
    'domicilio' => env('LEGAL_DOMICILIO', ''),
    'registro' => env('LEGAL_REGISTRO', ''),

    // La version que acepta quien se registra. Se guarda con la cuenta: al
    // cambiar los textos de forma sustancial, cambiar la fecha y pedir de
    // nuevo la aceptacion a quien tenga una anterior.
    'version' => '2026-09-29',

];
