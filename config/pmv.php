<?php

/*
|--------------------------------------------------------------------------
| El producto minimo viable
|--------------------------------------------------------------------------
|
| El PMV, en una frase: una promotora se registra, contrata, nos entrega el
| material de su proyecto, recibe un visor 3D publicado con sus viviendas,
| precios y contacto, y los compradores le llegan a su bandeja y a su
| WhatsApp. Nosotros montamos el visor.
|
| Todo lo que no esta en esa frase se aparta hasta despues del PMV. No se
| borra: se esconde. Esta hecho pero no lo ha probado nadie, y cada zona es
| una pestana mas entre la promotora y su primer visor, y un sitio mas donde
| romperse. El equipo (superadmin, gestor) lo sigue viendo todo.
|
| Cada zona lista los nombres de ruta (con comodines) y las direcciones sin
| nombre que la forman. Se usa desde el middleware FueraDelPmv, que responde
| 404 a quien no es del equipo, y desde las vistas con @pmv('zona').
|
*/

return [

    // Con esto en false, todo vuelve a verse. Es el interruptor para la fase
    // de salida, y para los tests que quieran mirar lo escondido.
    'activo' => env('PMV_ACTIVO', true),

    'fuera' => [
        'blog' => [
            'rutas' => ['admin.blog.*', 'blog.*'],
        ],
        'api' => [
            'rutas' => ['admin.api-tokens.*'],
            'uris' => ['api/v1/*'],
        ],
        'webhooks' => [
            'rutas' => ['admin.webhooks.*'],
        ],
        'mcp' => [
            'uris' => ['mcp', '.well-known/mcp.json'],
        ],
        'monedas' => [
            'rutas' => ['admin.currencies.*'],
        ],
        'analisis' => [
            'rutas' => ['admin.strategic-analysis.*'],
        ],
        'inversion' => [
            'rutas' => ['viewer.investment.pdf'],
        ],
        'obra' => [
            'rutas' => ['admin.projects.construction.*', 'api.construction.image'],
        ],
        'pagos' => [
            'rutas' => ['admin.projects.payment-plans.*', 'admin.projects.units.comprador*', 'viewer.payment-schedule.pdf'],
        ],
        'directorio' => [
            'rutas' => ['directory.*'],
        ],
        'widget' => [
            'rutas' => ['embed.show'],
        ],
    ],

];
