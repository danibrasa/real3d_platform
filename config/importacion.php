<?php

return [

    /*
     * Lectura de PDFs con un modelo.
     *
     * Se separa de config/chatbot.php a proposito: el chatbot atiende a
     * visitantes y le vale un modelo pequeño y barato, mientras que leer un
     * cuadro de superficies de un folleto mal maquetado necesita uno capaz. Son
     * dos decisiones distintas aunque hoy compartan la misma clave.
     */

    'pdf_habilitado' => env('IMPORT_PDF_ENABLED', true),

    'anthropic' => [
        'api_key' => env('IMPORT_ANTHROPIC_API_KEY', env('ANTHROPIC_API_KEY')),
        'model' => env('IMPORT_ANTHROPIC_MODEL', 'claude-sonnet-5'),
    ],

    // Los PDFs de folletos pesan: uno con renders a doble pagina se va a 15 MB
    // con facilidad. Los dos servidores tienen upload_max_filesize = 20M y
    // post_max_size = 25M, asi que 15 deja margen para el resto del formulario;
    // pedir mas se rechazaria en PHP antes de llegar a Laravel, y ahi el usuario
    // no ve un error util sino una pagina rota.
    'max_mb' => env('IMPORT_PDF_MAX_MB', 15),

    // OJO: php-fpm tiene max_execution_time = 120 en los dos servidores. Si esta
    // espera lo supera, PHP mata la peticion antes de que la API conteste y el
    // usuario ve un error en blanco despues de esperar dos minutos. Se deja
    // holgura para leer el fichero y montar la respuesta.
    // Si hiciera falta mas, el sitio correcto no es subir esto: es sacar la
    // lectura a un trabajo en cola con su pantalla de progreso.
    'segundos' => env('IMPORT_PDF_TIMEOUT', 100),

];
