<?php

namespace Tests\Feature;

use App\Jobs\PrepararFondo360;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El fondo 360 empieza ligero: una version de 2K al subir, y el visor la pide primero.
 *
 * La foto 360 de un proyecto real pesa 21 MB y el visor la cargaba entera
 * antes de enseñar nada. Y el tope de tamaño se miraba solo contra lo que
 * el cliente declaraba al empezar, no contra los bytes que llegaban.
 */
class Fondo360Test extends TestCase
{
    use RefreshDatabase;

    private User $equipo;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        $this->equipo = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $promotora = User::factory()->create(['role' => User::ROLE_INMOBILIARIA]);
        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora Bahia',
            'slug' => 'promotora-bahia',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
        ]);
        $this->proyecto = Project::create([
            'name' => 'Residencial Bahia',
            'slug' => 'residencial-bahia',
            'status' => 'public',
            'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($this->proyecto->id);
    }

    /** Un JPEG equirectangular de verdad, del ancho que se pida. */
    private function jpeg(int $ancho): string
    {
        $img = imagecreatetruecolor($ancho, (int) ($ancho / 2));
        imagefilledrectangle($img, 0, 0, $ancho, (int) ($ancho / 4), imagecolorallocate($img, 90, 160, 230));
        imagefilledrectangle($img, 0, (int) ($ancho / 4), $ancho, (int) ($ancho / 2), imagecolorallocate($img, 60, 120, 40));
        ob_start();
        imagejpeg($img, null, 85);
        imagedestroy($img);

        return ob_get_clean();
    }

    /** Sube por trozos como el equipo, con la cola en sync: el job corre dentro. */
    private function subir(string $contenido, string $tipo = 'image_360', string $nombre = 'fondo.jpg')
    {
        $trozos = str_split($contenido, 65536);
        $id = $this->actingAs($this->equipo)->postJson(route('admin.projects.upload.init', $this->proyecto), [
            'file_type' => $tipo, 'original_name' => $nombre,
            'total_size' => strlen($contenido), 'total_chunks' => count($trozos),
        ])->json('upload_id');

        foreach ($trozos as $i => $trozo) {
            $this->actingAs($this->equipo)->post(route('admin.projects.upload.chunk', $this->proyecto), [
                'upload_id' => $id, 'chunk_index' => $i,
                'chunk' => UploadedFile::fake()->createWithContent("t{$i}", $trozo),
            ]);
        }

        return $this->actingAs($this->equipo)->postJson(route('admin.projects.upload.complete', $this->proyecto), ['upload_id' => $id]);
    }

    public function test_de_un_fondo_grande_sale_una_version_de_2k(): void
    {
        $this->subir($this->jpeg(4096))->assertOk();

        $fondo = $this->proyecto->getFileByType('image_360');
        $this->assertNotNull($fondo->variantes['2k'] ?? null, 'no se saco la version ligera');
        Storage::assertExists($fondo->variantes['2k']);

        [$ancho] = getimagesize(Storage::path($fondo->variantes['2k']));
        $this->assertSame(PrepararFondo360::ANCHO_LIGERO, $ancho);
        $this->assertLessThan(strlen($this->jpeg(4096)), Storage::size($fondo->variantes['2k']));
    }

    public function test_un_fondo_ya_pequeño_no_tiene_variante_y_se_sirve_el_mismo(): void
    {
        $this->subir($this->jpeg(1024))->assertOk();

        $fondo = $this->proyecto->getFileByType('image_360');
        $this->assertNull($fondo->variantes);

        // Pedir la ligera no falla: cae al original, que es lo que habia.
        $this->get($this->proyecto->urlDeFichero('image_360', '2k'))
            ->assertOk()
            ->assertHeader('ETag', '"'.$fondo->version().'"');
    }

    public function test_la_ligera_se_sirve_con_tam_2k_y_por_nginx_apunta_a_ella(): void
    {
        $this->subir($this->jpeg(4096))->assertOk();
        $fondo = $this->proyecto->getFileByType('image_360');

        $url = $this->proyecto->urlDeFichero('image_360', '2k');
        $this->assertStringContainsString('tam=2k', $url);

        $r = $this->get($url)->assertOk();
        $this->assertSame('"'.$fondo->version().'-2k"', $r->headers->get('ETag'), 'la ligera y la grande no pueden compartir ETag');
        $this->assertStringContainsString('immutable', $r->headers->get('Cache-Control'));

        config(['ficheros.por_nginx' => true]);
        $this->get($url)->assertHeader('X-Accel-Redirect', '/_ficheros/'.$fondo->variantes['2k']);
    }

    public function test_lo_que_no_es_una_imagen_no_rompe_nada(): void
    {
        $this->subir(str_repeat('esto no es un jpeg', 100))->assertOk();

        $fondo = $this->proyecto->getFileByType('image_360');
        $this->assertNotNull($fondo);
        $this->assertNull($fondo->variantes);
    }

    public function test_el_tope_se_mira_contra_los_bytes_que_llegan_no_contra_lo_declarado(): void
    {
        // Lo dijo el revisor: declarar un total pequeño y mandar mas trozos.
        config(['ficheros.maximos_mb.image_360' => 0.0001]);   // ~105 bytes
        $contenido = $this->jpeg(256);

        $trozos = str_split($contenido, 65536);
        $id = $this->actingAs($this->equipo)->postJson(route('admin.projects.upload.init', $this->proyecto), [
            'file_type' => 'image_360', 'original_name' => 'fondo.jpg',
            'total_size' => 50, 'total_chunks' => count($trozos),
        ])->assertOk()->json('upload_id');
        foreach ($trozos as $i => $trozo) {
            $this->actingAs($this->equipo)->post(route('admin.projects.upload.chunk', $this->proyecto), [
                'upload_id' => $id, 'chunk_index' => $i,
                'chunk' => UploadedFile::fake()->createWithContent("t{$i}", $trozo),
            ]);
        }

        $this->actingAs($this->equipo)
            ->postJson(route('admin.projects.upload.complete', $this->proyecto), ['upload_id' => $id])
            ->assertStatus(422);

        $this->assertNull($this->proyecto->getFileByType('image_360'), 'entro un fichero por encima del tope');
        $this->assertCount(0, Storage::allFiles("projects/{$this->proyecto->id}"), 'el fichero grande se quedo en el disco');
    }

    public function test_el_visor_enseña_la_portada_mientras_carga(): void
    {
        $this->subir($this->jpeg(1024))->assertOk();
        $this->subir($this->jpeg(512), 'thumbnail', 'portada.jpg')->assertOk();

        $this->get(route('viewer.show', $this->proyecto))
            ->assertOk()
            ->assertSee('files/thumbnail?v=', false)
            ->assertSee('id="loading-overlay"', false);
    }
}
