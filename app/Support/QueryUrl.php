<?php

namespace App\Support;

/**
 * Construye enlaces conservando solo los parametros conocidos y con un valor valido.
 *
 * Sustituye a request()->fullUrlWithQuery(), que arrastraba cualquier parametro
 * de la URL actual. Un rastreador acabo generando URLs infinitas del tipo
 * ?lang=es¤cy=CAD¤cy=EUR... y cada visita quedaba registrada en viewer_events.
 */
class QueryUrl
{
    /** Parametros que se conservan y sus valores admitidos. */
    private const ALLOWED = [
        'lang' => ['es', 'en'],
        'currency' => ['USD', 'DOP', 'EUR', 'CAD'],
    ];

    /**
     * URL actual con los parametros indicados, descartando todo lo demas.
     *
     * @param  array<string, string>  $params
     */
    public static function with(array $params = []): string
    {
        $query = [];

        foreach (self::ALLOWED as $key => $allowedValues) {
            $value = request()->query($key);
            if (is_string($value) && in_array($value, $allowedValues, true)) {
                $query[$key] = $value;
            }
        }

        foreach ($params as $key => $value) {
            if (isset(self::ALLOWED[$key]) && in_array($value, self::ALLOWED[$key], true)) {
                $query[$key] = $value;
            }
        }

        return url()->current().($query ? '?'.http_build_query($query) : '');
    }
}
