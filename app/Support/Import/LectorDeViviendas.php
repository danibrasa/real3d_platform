<?php

namespace App\Support\Import;

use OpenSpout\Reader\CSV\Options as OpcionesCsv;
use OpenSpout\Reader\CSV\Reader as LectorCsv;
use OpenSpout\Reader\XLSX\Reader as LectorXlsx;

/**
 * Lee un Excel o CSV de viviendas e intenta entenderlo solo.
 *
 * No hay un formato estandar: cada promotora nombra las columnas a su manera y
 * escribe los precios como le parece. En vez de exigirles una plantilla, esto
 * reconoce los nombres habituales y limpia los valores. Lo que no reconozca, el
 * usuario lo corrige a mano antes de confirmar.
 */
class LectorDeViviendas
{
    /** Cuantas filas se enseñan en la vista previa. */
    public const FILAS_VISTA_PREVIA = 8;

    /** Tope de filas por importacion, para no tumbar el servidor. */
    public const MAX_FILAS = 2000;

    /**
     * Nombres con los que cada campo aparece en la vida real. Se comparan sin
     * acentos ni mayusculas. El orden importa: gana la primera coincidencia.
     *
     * @var array<string, list<string>>
     */
    private const SINONIMOS = [
        'identifier' => ['identificador', 'identificacion', 'unidad', 'vivienda', 'codigo', 'cod', 'ref', 'referencia', 'numero', 'num', 'n', 'nombre', 'piso n', 'apartamento', 'apto', 'unit', 'id'],
        'floor' => ['planta', 'piso', 'nivel', 'altura', 'floor', 'level'],
        'bedrooms' => ['dormitorios', 'dormitorio', 'habitaciones', 'habitacion', 'hab', 'dorm', 'cuartos', 'recamaras', 'bedrooms', 'beds'],
        'bathrooms' => ['banos', 'bano', 'aseos', 'aseo', 'servicios', 'bathrooms', 'baths', 'wc'],
        'area_m2' => ['superficie', 'superficie util', 'superficie construida', 'metros', 'metros cuadrados', 'm2', 'area', 'sup', 'tamano', 'size'],
        'price' => ['precio', 'precio venta', 'pvp', 'importe', 'valor', 'coste', 'price', 'amount'],
        'status' => ['estado', 'situacion', 'disponibilidad', 'status', 'disponible'],
        'typology' => ['tipologia', 'tipo', 'modelo', 'typology', 'type'],
        'notes' => ['notas', 'observaciones', 'comentarios', 'descripcion', 'notes', 'remarks'],
    ];

    /** Como se escribe cada estado por ahi. */
    private const ESTADOS = [
        'available' => ['disponible', 'libre', 'available', 'en venta', 'venta', 'si', 'activo'],
        'reserved' => ['reservado', 'reservada', 'reserved', 'senal', 'senalizado', 'opcion'],
        'sold' => ['vendido', 'vendida', 'sold', 'no disponible', 'ocupado', 'no'],
    ];

    /**
     * Lee el fichero y devuelve lo que ha entendido.
     *
     * @return array{cabeceras: list<string>, filas: list<array<int, string>>, mapeo: array<string, int|null>, total: int}
     */
    public function leer(string $ruta): array
    {
        $lector = $this->lectorPara($ruta);
        $lector->open($ruta);

        $cabeceras = [];
        $filas = [];
        $numero = 0;

        foreach ($lector->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $celdas = array_map(
                    fn ($c) => $this->aTexto($c->getValue()),
                    $fila->getCells()
                );

                // La primera fila con algo escrito son las cabeceras.
                if ($cabeceras === []) {
                    if ($this->filaVacia($celdas)) {
                        continue;
                    }
                    $cabeceras = $celdas;

                    continue;
                }

                if ($this->filaVacia($celdas)) {
                    continue;
                }

                $filas[] = $celdas;
                if (++$numero >= self::MAX_FILAS) {
                    break 2;
                }
            }
            break; // solo la primera hoja
        }

        $lector->close();

        return [
            'cabeceras' => $cabeceras,
            'filas' => $filas,
            'mapeo' => $this->adivinarMapeo($cabeceras),
            'total' => count($filas),
        ];
    }

    /**
     * Empareja cada campo con la columna que mas se le parece.
     *
     * @param  list<string>  $cabeceras
     * @return array<string, int|null>
     */
    public function adivinarMapeo(array $cabeceras): array
    {
        $normalizadas = array_map([$this, 'normalizar'], $cabeceras);
        $mapeo = [];
        $usadas = [];

        foreach (self::SINONIMOS as $campo => $sinonimos) {
            $mapeo[$campo] = null;

            // Primero, coincidencia exacta: es la que menos se equivoca.
            foreach ($sinonimos as $sinonimo) {
                $i = array_search($sinonimo, $normalizadas, true);
                if ($i !== false && ! in_array($i, $usadas, true)) {
                    $mapeo[$campo] = $i;
                    $usadas[] = $i;

                    continue 2;
                }
            }

            // Si no, que la cabecera contenga el sinonimo ("precio de venta").
            // Se exige al menos 3 letras para que "n" o "id" no arrasen.
            foreach ($sinonimos as $sinonimo) {
                if (mb_strlen($sinonimo) < 3) {
                    continue;
                }
                foreach ($normalizadas as $i => $cabecera) {
                    if (in_array($i, $usadas, true) || $cabecera === '') {
                        continue;
                    }
                    if (str_contains($cabecera, $sinonimo)) {
                        $mapeo[$campo] = $i;
                        $usadas[] = $i;

                        continue 3;
                    }
                }
            }
        }

        return $mapeo;
    }

    /**
     * Convierte una fila cruda en los campos de una vivienda.
     *
     * @param  array<int, string>  $fila
     * @param  array<string, int|null>  $mapeo
     * @return array<string, mixed>
     */
    public function interpretar(array $fila, array $mapeo): array
    {
        $valor = fn (string $campo) => isset($mapeo[$campo]) && $mapeo[$campo] !== null
            ? trim($fila[$mapeo[$campo]] ?? '')
            : '';

        return [
            'identifier' => $valor('identifier'),
            'floor' => $this->aEntero($valor('floor')),
            'bedrooms' => $this->aEntero($valor('bedrooms')),
            'bathrooms' => $this->aEntero($valor('bathrooms')),
            'area_m2' => $this->aDecimal($valor('area_m2')),
            'price' => $this->aDecimal($valor('price')),
            'status' => $this->aEstado($valor('status')),
            'typology' => $valor('typology') ?: null,
            'notes' => $valor('notes') ?: null,
        ];
    }

    /**
     * Quita acentos, signos y mayusculas para poder comparar cabeceras.
     */
    public function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            '²' => '2', '³' => '3', 'º' => '', 'ª' => '',
        ]);
        $texto = preg_replace('/[^a-z0-9 ]+/', ' ', $texto) ?? '';

        return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
    }

    /**
     * Interpreta un numero escrito como le venga en gana a quien lo escribio:
     * "185.000 €", "1,250.50", "85 m2", "2 dorm".
     */
    public function aDecimal(string $texto): ?float
    {
        $texto = trim($texto);
        if ($texto === '') {
            return null;
        }

        // Primero fuera las unidades del final: si no, su cifra se pega al
        // numero y "85 m2" acaba siendo 852.
        $texto = preg_replace('/\s*(m2|mts2|metros|m\.?c\.?)\s*$/iu', '', $texto) ?? $texto;
        $texto = preg_replace('/\s*(eur|euros?|usd|dolares?|dop|cad)\s*$/iu', '', $texto) ?? $texto;

        // Y ahora fuera todo lo que no sea digito, coma, punto o signo.
        $limpio = preg_replace('/[^0-9,.\-]/', '', $texto) ?? '';
        if ($limpio === '' || $limpio === '-') {
            return null;
        }

        $ultimaComa = strrpos($limpio, ',');
        $ultimoPunto = strrpos($limpio, '.');

        if ($ultimaComa !== false && $ultimoPunto !== false) {
            // Manda el ultimo: el otro separa los miles.
            $decimal = $ultimaComa > $ultimoPunto ? ',' : '.';
            $miles = $decimal === ',' ? '.' : ',';
            $limpio = str_replace($miles, '', $limpio);
            $limpio = str_replace($decimal, '.', $limpio);
        } elseif ($ultimaComa !== false) {
            // Una sola coma: decimal si deja 1 o 2 cifras detras ("1,5"),
            // miles si deja justo 3 ("185,000").
            $detras = strlen($limpio) - $ultimaComa - 1;
            $limpio = $detras === 3
                ? str_replace(',', '', $limpio)
                : str_replace(',', '.', $limpio);
        } elseif ($ultimoPunto !== false) {
            $detras = strlen($limpio) - $ultimoPunto - 1;
            // "185.000" son miles; "85.5" son decimales.
            if ($detras === 3 && substr_count($limpio, '.') >= 1 && strlen($limpio) > 4) {
                $limpio = str_replace('.', '', $limpio);
            }
        }

        return is_numeric($limpio) ? (float) $limpio : null;
    }

    public function aEntero(string $texto): ?int
    {
        $n = $this->aDecimal($texto);

        return $n === null ? null : (int) round($n);
    }

    /**
     * Traduce el estado. Lo que no se reconoce se da por disponible, que es lo
     * que menos daño hace: aparece a la venta y se corrige, en vez de ocultarse.
     */
    public function aEstado(string $texto): string
    {
        $t = $this->normalizar($texto);
        if ($t === '') {
            return 'available';
        }

        foreach (self::ESTADOS as $estado => $formas) {
            foreach ($formas as $forma) {
                if ($t === $forma || str_contains($t, $forma)) {
                    return $estado;
                }
            }
        }

        return 'available';
    }

    /** Campos que el usuario puede emparejar a mano, con su nombre visible. */
    public static function camposDisponibles(): array
    {
        return [
            'identifier' => 'Identificador',
            'floor' => 'Planta',
            'bedrooms' => 'Dormitorios',
            'bathrooms' => 'Baños',
            'area_m2' => 'Superficie (m²)',
            'price' => 'Precio',
            'status' => 'Estado',
            'typology' => 'Tipología',
            'notes' => 'Notas',
        ];
    }

    /**
     * Devuelve el lector adecuado al fichero, con el separador que toque.
     */
    private function lectorPara(string $ruta): LectorCsv|LectorXlsx
    {
        if (mb_strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) !== 'csv') {
            return new LectorXlsx;
        }

        $opciones = new OpcionesCsv;
        $opciones->FIELD_DELIMITER = $this->detectarSeparador($ruta);

        return new LectorCsv($opciones);
    }

    /**
     * Adivina el separador del CSV contando cual aparece mas en la cabecera.
     *
     * Excel en castellano exporta con ';' porque la coma es el decimal; en
     * ingles exporta con ','. Sin esto, la fila entera cae en una sola columna.
     */
    public function detectarSeparador(string $ruta): string
    {
        $fh = @fopen($ruta, 'r');
        if ($fh === false) {
            return ',';
        }
        $primera = fgets($fh, 8192) ?: '';
        fclose($fh);

        $cuenta = [];
        foreach ([';', ',', "\t", '|'] as $candidato) {
            $cuenta[$candidato] = substr_count($primera, $candidato);
        }
        arsort($cuenta);
        $mejor = array_key_first($cuenta);

        return $cuenta[$mejor] > 0 ? $mejor : ',';
    }

    private function aTexto(mixed $valor): string
    {
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        return trim((string) $valor);
    }

    /** @param array<int, string> $celdas */
    private function filaVacia(array $celdas): bool
    {
        foreach ($celdas as $c) {
            if (trim($c) !== '') {
                return false;
            }
        }

        return true;
    }
}
