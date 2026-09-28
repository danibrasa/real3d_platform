<?php

namespace Tests\Feature;

use App\Support\Import\LectorDeDocumentos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El lector de PDFs, sin gastar llamadas a la API.
 *
 * Lo que se prueba aqui no es si el modelo lee bien un folleto -eso se
 * comprueba a mano con uno de verdad- sino que lo que devuelva se trate con
 * cabeza: que no se cuele una vivienda sin identificador, que un hueco siga
 * siendo un hueco, y que una respuesta rara falle de forma limpia en vez de
 * crear basura.
 */
class LectorDeDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private function responde(string $json): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $json]],
            ], 200),
        ]);
    }

    private function pdfFalso(): string
    {
        Storage::fake('local');
        $ruta = sys_get_temp_dir().'/prueba-'.uniqid().'.pdf';
        file_put_contents($ruta, '%PDF-1.4 fichero de prueba');

        return $ruta;
    }

    private function leer(string $json): array
    {
        $this->responde($json);

        return (new LectorDeDocumentos(clave: 'clave-de-prueba', modelo: 'modelo-de-prueba'))
            ->leer($this->pdfFalso());
    }

    public function test_devuelve_la_misma_forma_que_el_lector_de_excel(): void
    {
        // Es lo que permite que el PDF entre por el mismo circuito, con la misma
        // pantalla de revision. Si esto cambia, hay dos caminos que mantener.
        $leido = $this->leer('{"viviendas":[{"identifier":"A-101","floor":1,"bedrooms":2,
            "bathrooms":2,"area_m2":85.5,"price":185000,"status":"available",
            "typology":"Tipo A","notes":null}]}');

        $this->assertSame(LectorDeDocumentos::CAMPOS, $leido['cabeceras']);
        $this->assertSame(1, $leido['total']);
        $this->assertCount(count(LectorDeDocumentos::CAMPOS), $leido['filas'][0]);
        $this->assertSame('A-101', $leido['filas'][0][0]);
        $this->assertSame('185000', $leido['filas'][0][5]);
    }

    public function test_un_hueco_sigue_siendo_un_hueco(): void
    {
        // El caso que importa: el folleto pone "Consultar" en vez de un precio.
        // Preferimos un hueco, que se puede rellenar, a un numero inventado, que
        // nadie sabria que lo es.
        $leido = $this->leer('{"viviendas":[{"identifier":"B-102","price":null,
            "area_m2":55,"status":"available"}]}');

        $this->assertSame('', $leido['filas'][0][5]);
        $this->assertSame('55', $leido['filas'][0][4]);
    }

    public function test_una_vivienda_sin_identificador_no_cuenta(): void
    {
        // Sin identificador no hay nada que crear, y colarla haria que el
        // recuento que se le enseña a la promotora fuera mentira.
        $leido = $this->leer('{"viviendas":[
            {"identifier":"A-101","price":100},
            {"identifier":"","price":200},
            {"identifier":"   ","price":300},
            {"price":400}
        ]}');

        $this->assertSame(1, $leido['total']);
    }

    public function test_aguanta_que_llegue_envuelto_en_markdown(): void
    {
        // Por muy claras que sean las instrucciones, a veces llega con ```json.
        $leido = $this->leer("Aqui tienes:\n```json\n{\"viviendas\":[{\"identifier\":\"A-1\"}]}\n```");

        $this->assertSame(1, $leido['total']);
    }

    public function test_una_respuesta_que_no_es_json_falla_limpio(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->leer('No he podido leer el documento.');
    }

    public function test_un_json_sin_viviendas_falla_limpio(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->leer('{"resultado":"vacio"}');
    }

    public function test_un_documento_sin_viviendas_devuelve_cero_y_no_revienta(): void
    {
        // Un render o una memoria de calidades: no hay nada que importar, y eso
        // no es un error.
        $leido = $this->leer('{"viviendas":[]}');

        $this->assertSame(0, $leido['total']);
        $this->assertSame([], $leido['filas']);
    }

    public function test_sin_clave_no_intenta_llamar_a_nadie(): void
    {
        Http::fake();

        $lector = new LectorDeDocumentos(clave: '', modelo: 'x');

        $this->assertFalse($lector->disponible());
        $this->expectException(\RuntimeException::class);
        $lector->leer($this->pdfFalso());
    }

    public function test_si_la_api_falla_se_dice_y_no_se_inventa_nada(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response('sin cuota', 429)]);

        $this->expectException(\RuntimeException::class);
        (new LectorDeDocumentos(clave: 'x', modelo: 'y'))->leer($this->pdfFalso());
    }

    // --- Imagenes ---------------------------------------------------------

    private function ficheroFalso(string $extension): string
    {
        $ruta = sys_get_temp_dir().'/prueba-'.uniqid().'.'.$extension;
        file_put_contents($ruta, 'contenido de prueba');

        return $ruta;
    }

    public function test_lee_tambien_una_foto_del_listado(): void
    {
        // No todas las promotoras tienen el listado en PDF: muchas lo tienen en
        // una imagen dentro de una presentacion, o le hacen una foto al cuadro
        // impreso. Para quien sube el fichero tiene que ser lo mismo.
        $this->responde('{"viviendas":[{"identifier":"M-201","price":168000}]}');

        $leido = (new LectorDeDocumentos(clave: 'x', modelo: 'y'))
            ->leer($this->ficheroFalso('png'));

        $this->assertSame(1, $leido['total']);
    }

    public function test_una_imagen_va_como_imagen_y_un_pdf_como_documento(): void
    {
        // La API los trata distinto: mandar una foto como "document" falla.
        foreach (['pdf' => 'document', 'jpg' => 'image', 'png' => 'image', 'webp' => 'image'] as $ext => $esperado) {
            $this->responde('{"viviendas":[]}');

            (new LectorDeDocumentos(clave: 'x', modelo: 'y'))->leer($this->ficheroFalso($ext));

            Http::assertSent(function ($peticion) use ($esperado) {
                return $peticion['messages'][0]['content'][0]['type'] === $esperado;
            });
        }
    }

    public function test_un_formato_que_no_se_sabe_leer_se_dice(): void
    {
        $this->responde('{"viviendas":[]}');

        $this->expectException(\RuntimeException::class);
        (new LectorDeDocumentos(clave: 'x', modelo: 'y'))->leer($this->ficheroFalso('docx'));
    }
}
