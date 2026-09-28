<?php

namespace App\Support\Import;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Saca las viviendas de un documento: un folleto en PDF, un listado de precios,
 * una captura de pantalla o la foto de un cuadro impreso.
 *
 * Existe porque el importador de Excel, por bien que se trague un fichero sucio,
 * sigue exigiendo un Excel. Y lo que las promotoras tienen de verdad son
 * folletos y planos: pedirles que primero monten una hoja de calculo es
 * devolverles el trabajo que este producto promete quitarles.
 *
 * Devuelve EXACTAMENTE la misma forma que LectorDeViviendas, a proposito: asi el
 * PDF entra por el mismo circuito, con la misma pantalla de revision y el mismo
 * confirmar. Lo que llega de un modelo hay que revisarlo antes de crear nada, y
 * esa pantalla ya existe y esta probada.
 */
class LectorDeDocumentos
{
    /** Lo que se sabe leer, y como se lo llama la API. */
    public const TIPOS = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    /** Los mismos campos que entiende el importador de Excel. */
    public const CAMPOS = [
        'identifier', 'floor', 'bedrooms', 'bathrooms',
        'area_m2', 'price', 'status', 'typology', 'notes',
    ];

    private const INSTRUCCIONES = <<<'TXT'
        Eres un extractor de datos para una plataforma inmobiliaria. Te dan el
        documento de un proyecto de obra nueva (folleto, listado de precios,
        memoria de calidades o plano), a veces en PDF y a veces como foto o
        captura de pantalla, y devuelves las viviendas que aparecen.

        Devuelve SOLO un objeto JSON, sin texto alrededor y sin markdown:

        {"viviendas": [{"identifier": "A-101", "floor": 1, "bedrooms": 2,
          "bathrooms": 2, "area_m2": 85.5, "price": 185000, "status": "available",
          "typology": "Tipo A", "notes": null}]}

        Reglas, en orden de importancia:

        1. NO TE INVENTES NADA. Si un dato no aparece en el documento, pon null.
           Es mucho mejor un hueco que un numero plausible: quien revise esto
           puede rellenar un hueco, pero no puede adivinar que un precio esta mal.
        2. `identifier` es obligatorio: si una vivienda no tiene identificador
           (A-101, 2B, Apto 305...), no la incluyas.
        3. `price` y `area_m2` en numero, sin simbolos ni separadores de miles.
           "US$ 185.000" es 185000. "85,5 m2" es 85.5.
        4. `status` solo puede ser "available", "reserved" o "sold". Disponible,
           libre o en venta es available. Reservado es reserved. Vendido es sold.
           Si no lo dice, pon "available".
        5. Si el documento no lista viviendas una a una (es solo un render, una
           memoria de calidades o un plano sin cuadro de superficies), devuelve
           {"viviendas": []}. No deduzcas unidades de un plano dibujado.
        6. Si los precios estan en una moneda que no sea dolares, ponlo en
           `notes` de cada vivienda. No conviertas.
        TXT;

    public function __construct(
        private ?string $clave = null,
        private ?string $modelo = null,
        private int $segundos = 180,
    ) {
        $this->clave ??= config('importacion.anthropic.api_key');
        $this->modelo ??= config('importacion.anthropic.model');
    }

    public function disponible(): bool
    {
        return ! empty($this->clave);
    }

    /**
     * @return array{cabeceras: array, filas: array, mapeo: array, total: int}
     */
    public function leer(string $ruta): array
    {
        if (! $this->disponible()) {
            throw new RuntimeException('No hay clave de API configurada para leer documentos.');
        }

        $viviendas = $this->preguntar($ruta);

        // Se devuelve con la forma del lector de Excel: cabeceras, filas en el
        // mismo orden, y un mapeo que aqui es directo porque los campos ya
        // vienen con su nombre. Asi la pantalla de revision no distingue si
        // esto vino de un Excel o de un PDF.
        $filas = [];
        foreach ($viviendas as $v) {
            $fila = [];
            foreach (self::CAMPOS as $campo) {
                $valor = $v[$campo] ?? null;
                $fila[] = $valor === null ? '' : (string) $valor;
            }
            $filas[] = $fila;
        }

        return [
            'cabeceras' => self::CAMPOS,
            'filas' => $filas,
            'mapeo' => array_combine(self::CAMPOS, array_keys(self::CAMPOS)),
            'total' => count($filas),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function preguntar(string $ruta): array
    {
        $respuesta = Http::withHeaders([
            'x-api-key' => $this->clave,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout($this->segundos)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->modelo,
            'max_tokens' => 8000,
            'system' => self::INSTRUCCIONES,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    $this->bloqueDelFichero($ruta),
                    [
                        'type' => 'text',
                        'text' => 'Extrae las viviendas de este documento. Si es una '
                            .'foto o una captura, lee la tabla aunque este torcida o '
                            .'recortada, y no inventes las filas que no se lean.',
                    ],
                ],
            ]],
        ]);

        if (! $respuesta->successful()) {
            throw new RuntimeException(
                'La API respondio '.$respuesta->status().': '.substr($respuesta->body(), 0, 300)
            );
        }

        return $this->interpretarRespuesta($respuesta->json('content.0.text', ''));
    }

    /**
     * El fichero, en el formato que espera la API.
     *
     * Un PDF va como `document` y una imagen como `image`: son bloques
     * distintos aunque para quien sube el fichero sea lo mismo. Se acepta la
     * foto o la captura porque no todas las promotoras tienen el listado en un
     * PDF; muchas lo tienen en una imagen dentro de una presentacion, o le hacen
     * una foto al cuadro de precios impreso.
     *
     * @return array<string, mixed>
     */
    private function bloqueDelFichero(string $ruta): array
    {
        $tipo = self::TIPOS[strtolower(pathinfo($ruta, PATHINFO_EXTENSION))] ?? null;

        if ($tipo === null) {
            throw new RuntimeException('Formato no admitido: '.pathinfo($ruta, PATHINFO_EXTENSION));
        }

        return [
            'type' => $tipo === 'application/pdf' ? 'document' : 'image',
            'source' => [
                'type' => 'base64',
                'media_type' => $tipo,
                'data' => base64_encode(file_get_contents($ruta)),
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function interpretarRespuesta(string $texto): array
    {
        // Por muy claras que sean las instrucciones, a veces llega envuelto en
        // ```json. Se busca el objeto en vez de confiar en que venga limpio.
        if (preg_match('/\{.*\}/s', $texto, $coincidencias)) {
            $texto = $coincidencias[0];
        }

        $datos = json_decode($texto, true);

        if (! is_array($datos) || ! isset($datos['viviendas']) || ! is_array($datos['viviendas'])) {
            throw new RuntimeException('La respuesta no tenia el formato esperado.');
        }

        $fuera = [];
        foreach ($datos['viviendas'] as $v) {
            if (! is_array($v)) {
                continue;
            }

            // Sin identificador no hay vivienda que crear. Se filtra aqui y no
            // mas adelante para que el recuento que se le enseña a la promotora
            // sea el de verdad.
            $id = trim((string) ($v['identifier'] ?? ''));
            if ($id === '') {
                continue;
            }

            $limpia = ['identifier' => $id];
            foreach (self::CAMPOS as $campo) {
                if ($campo !== 'identifier') {
                    $limpia[$campo] = $v[$campo] ?? null;
                }
            }
            $fuera[] = $limpia;
        }

        return $fuera;
    }
}
