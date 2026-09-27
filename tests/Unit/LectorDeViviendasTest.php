<?php

namespace Tests\Unit;

use App\Support\Import\LectorDeViviendas;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * El importador existe para que una promotora no tenga que reformatear su
 * Excel. Estos tests son ejemplos de lo que manda gente real: cabeceras en
 * castellano o ingles, precios con euros y puntos de millar, superficies con
 * "m2" pegado, estados escritos de diez maneras.
 */
class LectorDeViviendasTest extends TestCase
{
    private LectorDeViviendas $lector;

    /** @var list<string> */
    private array $temporales = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->lector = new LectorDeViviendas;
    }

    protected function tearDown(): void
    {
        foreach ($this->temporales as $ruta) {
            @unlink($ruta);
        }
        parent::tearDown();
    }

    // --- Adivinar que columna es cada cosa -------------------------------

    public function test_reconoce_cabeceras_en_castellano(): void
    {
        $mapeo = $this->lector->adivinarMapeo([
            'Unidad', 'Planta', 'Dormitorios', 'Baños', 'Superficie', 'Precio', 'Estado',
        ]);

        $this->assertSame(0, $mapeo['identifier']);
        $this->assertSame(1, $mapeo['floor']);
        $this->assertSame(2, $mapeo['bedrooms']);
        $this->assertSame(3, $mapeo['bathrooms']);
        $this->assertSame(4, $mapeo['area_m2']);
        $this->assertSame(5, $mapeo['price']);
        $this->assertSame(6, $mapeo['status']);
    }

    public function test_reconoce_cabeceras_en_ingles(): void
    {
        $mapeo = $this->lector->adivinarMapeo(['Unit', 'Floor', 'Bedrooms', 'Baths', 'Area', 'Price']);

        $this->assertSame(0, $mapeo['identifier']);
        $this->assertSame(1, $mapeo['floor']);
        $this->assertSame(2, $mapeo['bedrooms']);
        $this->assertSame(5, $mapeo['price']);
    }

    public function test_reconoce_cabeceras_largas(): void
    {
        // Nadie escribe "price": escriben "Precio de venta (€)".
        $mapeo = $this->lector->adivinarMapeo([
            'Código de vivienda', 'Nº de planta', 'Superficie construida m2', 'Precio de venta (€)',
        ]);

        $this->assertSame(0, $mapeo['identifier']);
        $this->assertSame(1, $mapeo['floor']);
        $this->assertSame(2, $mapeo['area_m2']);
        $this->assertSame(3, $mapeo['price']);
    }

    public function test_no_asigna_dos_campos_a_la_misma_columna(): void
    {
        $mapeo = $this->lector->adivinarMapeo(['Unidad', 'Precio']);
        $usadas = array_filter($mapeo, fn ($v) => $v !== null);

        $this->assertSame(count($usadas), count(array_unique($usadas)));
    }

    public function test_deja_sin_asignar_lo_que_no_reconoce(): void
    {
        // Lo que no entiende se queda a null para que el usuario lo empareje.
        $mapeo = $this->lector->adivinarMapeo(['Columna rara', 'Otra cosa']);

        $this->assertNull($mapeo['price']);
        $this->assertNull($mapeo['area_m2']);
    }

    // --- Interpretar numeros escritos de cualquier manera ----------------

    #[DataProvider('preciosDelMundoReal')]
    public function test_interpreta_precios(string $escrito, ?float $esperado): void
    {
        $this->assertSame($esperado, $this->lector->aDecimal($escrito));
    }

    public static function preciosDelMundoReal(): array
    {
        return [
            'entero pelado' => ['185000', 185000.0],
            'con euro y puntos' => ['185.000 €', 185000.0],
            'con dolar y comas' => ['$185,000', 185000.0],
            'con decimales a la espanola' => ['1.250,50', 1250.5],
            'con decimales a la inglesa' => ['1,250.50', 1250.5],
            'superficie con unidad' => ['85 m2', 85.0],
            'superficie con decimal' => ['85,5 m²', 85.5],
            'miles sin decimales' => ['185,000', 185000.0],
            'vacio' => ['', null],
            'texto sin numeros' => ['consultar', null],
            'espacios de mas' => ['  92.500 €  ', 92500.0],
        ];
    }

    public function test_redondea_al_convertir_a_entero(): void
    {
        $this->assertSame(3, $this->lector->aEntero('3'));
        $this->assertSame(3, $this->lector->aEntero('2,8'));
        $this->assertNull($this->lector->aEntero(''));
    }

    // --- Traducir el estado ----------------------------------------------

    #[DataProvider('estadosDelMundoReal')]
    public function test_interpreta_estados(string $escrito, string $esperado): void
    {
        $this->assertSame($esperado, $this->lector->aEstado($escrito));
    }

    public static function estadosDelMundoReal(): array
    {
        return [
            'disponible' => ['Disponible', 'available'],
            'libre' => ['LIBRE', 'available'],
            'en venta' => ['En venta', 'available'],
            'reservado' => ['Reservado', 'reserved'],
            'reservada con acento' => ['Reservada', 'reserved'],
            'vendido' => ['VENDIDO', 'sold'],
            'vendida' => ['vendida', 'sold'],
            'en ingles' => ['sold', 'sold'],
            'vacio se da por disponible' => ['', 'available'],
            'desconocido se da por disponible' => ['pendiente de firma', 'available'],
        ];
    }

    public function test_lo_desconocido_sale_a_la_venta_no_se_oculta(): void
    {
        // Preferimos equivocarnos enseñando una vivienda que escondiendola:
        // lo primero se ve y se corrige, lo segundo pasa desapercibido.
        $this->assertSame('available', $this->lector->aEstado('???'));
    }

    // --- Interpretar una fila entera --------------------------------------

    public function test_interpreta_una_fila_completa(): void
    {
        $mapeo = $this->lector->adivinarMapeo(['Unidad', 'Planta', 'Dorm', 'Baños', 'Sup', 'Precio', 'Estado']);
        $fila = ['A-101', '1', '2', '2', '85,5 m²', '185.000 €', 'Disponible'];

        $v = $this->lector->interpretar($fila, $mapeo);

        $this->assertSame('A-101', $v['identifier']);
        $this->assertSame(1, $v['floor']);
        $this->assertSame(2, $v['bedrooms']);
        $this->assertSame(85.5, $v['area_m2']);
        $this->assertSame(185000.0, $v['price']);
        $this->assertSame('available', $v['status']);
    }

    public function test_una_fila_incompleta_no_revienta(): void
    {
        // Falta media tabla: debe devolver nulos, no lanzar excepcion.
        $mapeo = $this->lector->adivinarMapeo(['Unidad', 'Precio']);
        $v = $this->lector->interpretar(['A-101', '185000'], $mapeo);

        $this->assertSame('A-101', $v['identifier']);
        $this->assertSame(185000.0, $v['price']);
        $this->assertNull($v['bedrooms']);
        $this->assertNull($v['area_m2']);
    }

    public function test_normaliza_acentos_y_signos(): void
    {
        $this->assertSame('banos', $this->lector->normalizar('Baños'));
        $this->assertSame('n de planta', $this->lector->normalizar('Nº de planta'));
        $this->assertSame('superficie m2', $this->lector->normalizar('  Superficie (m²)  '));
    }

    // --- Leer ficheros de verdad ------------------------------------------

    public function test_lee_un_csv_en_castellano_separado_por_punto_y_coma(): void
    {
        // Excel en castellano exporta con ';' porque la coma es el decimal.
        // Sin detectarlo, la fila entera caia en una sola columna.
        $ruta = $this->ficheroTemporal('espanol.csv', <<<'CSV'
        Código;Nº Planta;Dormitorios;Baños;Superficie (m²);Precio de venta (€);Situación
        A-101;1;2;2;85,50;185.000 €;Disponible
        A-102;1;3;2;102,30;214.500 €;Reservado
        CSV);

        $r = $this->lector->leer($ruta);

        $this->assertCount(7, $r['cabeceras'], 'no ha separado las columnas');
        $this->assertSame(2, $r['total']);

        $v = $this->lector->interpretar($r['filas'][0], $r['mapeo']);
        $this->assertSame('A-101', $v['identifier']);
        $this->assertSame(85.5, $v['area_m2']);
        $this->assertSame(185000.0, $v['price']);
        $this->assertSame('available', $v['status']);
    }

    public function test_lee_un_csv_en_ingles_separado_por_comas(): void
    {
        $ruta = $this->ficheroTemporal('ingles.csv', <<<'CSV'
        Unit,Floor,Beds,Baths,Area,Price,Status
        PH-01,10,4,3,180,"1,250,000",Available
        CSV);

        $r = $this->lector->leer($ruta);
        $v = $this->lector->interpretar($r['filas'][0], $r['mapeo']);

        $this->assertSame('PH-01', $v['identifier']);
        $this->assertSame(10, $v['floor']);
        $this->assertSame(1250000.0, $v['price']);
    }

    public function test_encuentra_las_columnas_aunque_esten_desordenadas(): void
    {
        // El identificador no tiene por que ir el primero.
        $ruta = $this->ficheroTemporal('desordenado.csv', <<<'CSV'
        Precio;Referencia;Observaciones;Tipo;Habitaciones
        92500;C-01;Con terraza;Tipo A;1
        CSV);

        $r = $this->lector->leer($ruta);
        $v = $this->lector->interpretar($r['filas'][0], $r['mapeo']);

        $this->assertSame('C-01', $v['identifier']);
        $this->assertSame(92500.0, $v['price']);
        $this->assertSame('Tipo A', $v['typology']);
        $this->assertSame('Con terraza', $v['notes']);
    }

    public function test_se_salta_las_filas_vacias(): void
    {
        $ruta = $this->ficheroTemporal('huecos.csv', <<<'CSV'
        Unidad;Precio

        A-01;100000

        A-02;120000
        CSV);

        $this->assertSame(2, $this->lector->leer($ruta)['total']);
    }

    public function test_detecta_el_separador(): void
    {
        $this->assertSame(';', $this->lector->detectarSeparador(
            $this->ficheroTemporal('pyc.csv', "a;b;c\n1;2;3")
        ));
        $this->assertSame(',', $this->lector->detectarSeparador(
            $this->ficheroTemporal('coma.csv', "a,b,c\n1,2,3")
        ));
    }

    /** Crea un fichero temporal que se borra al terminar el test. */
    private function ficheroTemporal(string $nombre, string $contenido): string
    {
        $ruta = sys_get_temp_dir().'/test-import-'.uniqid().'-'.$nombre;
        file_put_contents($ruta, $contenido);
        $this->temporales[] = $ruta;

        return $ruta;
    }
}
