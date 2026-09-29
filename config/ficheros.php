<?php

return [

    /*
    | Quien sirve el modelo, el fondo 360 y el video: nginx o PHP.
    |
    | Con esto en true, PHP decide (permiso, plan, cache) y devuelve
    | X-Accel-Redirect; nginx lee el fichero del disco privado por un location
    | interno (deploy/nginx-ficheros.conf) con sendfile y rangos, sin que un
    | proceso php-fpm tenga que leer 35 MB y escribirlos. En false, PHP lo
    | manda el mismo: vale para tests y para una maquina sin ese location.
    */
    'por_nginx' => env('FICHEROS_POR_NGINX', false),

    /*
    | Lo maximo que se acepta subir por tipo, en MB. Por encima hay que
    | recomprimir antes: un video 360 de 315 MB son cinco minutos de 4G para
    | un comprador, y en produccion habia cuatro copias del mismo.
    */
    'maximos_mb' => [
        'video_360' => 100,
        'model_3d' => 60,
        'image_360' => 30,
        'ground_texture' => 10,
        'thumbnail' => 5,
    ],

];
