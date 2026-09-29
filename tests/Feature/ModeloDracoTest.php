<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El modelo 3D se comprime con Draco al subirlo, y el visor recibe el comprimido.
 *
 * El modelo de un proyecto real pesa 35 MB sin comprimir; con Draco son 5 u
 * 8, y el visor ya sabia leerlo desde el principio. Estos tests pasan por
 * gltf-pipeline de verdad, con un GLB de verdad: si la herramienta no esta
 * instalada (npm ci), fallan, y eso es lo que tienen que hacer.
 */
class ModeloDracoTest extends TestCase
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

    /**
     * Un GLB minimo pero de verdad: una malla de N triangulos con indices.
     *
     * Con pocos vertices Draco no ahorra nada, asi que se hacen suficientes
     * como para que comprimir compense, que es la condicion para guardarlo.
     */
    private function glb(int $triangulos = 400): string
    {
        $posiciones = '';
        $indices = '';
        for ($i = 0; $i < $triangulos * 3; $i++) {
            $posiciones .= pack('g3', ($i % 37) / 7, (($i * 7) % 53) / 11, ($i % 5) / 3);
            $indices .= pack('v', $i);
        }
        $bin = $posiciones.$indices;
        $bin .= str_repeat("\0", (4 - strlen($bin) % 4) % 4);

        $json = json_encode([
            'asset' => ['version' => '2.0'],
            'scene' => 0,
            'scenes' => [['nodes' => [0]]],
            'nodes' => [['mesh' => 0]],
            'meshes' => [['primitives' => [['attributes' => ['POSITION' => 0], 'indices' => 1]]]],
            'accessors' => [
                ['bufferView' => 0, 'componentType' => 5126, 'count' => $triangulos * 3, 'type' => 'VEC3', 'min' => [0, 0, 0], 'max' => [6, 5, 2]],
                ['bufferView' => 1, 'componentType' => 5123, 'count' => $triangulos * 3, 'type' => 'SCALAR'],
            ],
            'bufferViews' => [
                ['buffer' => 0, 'byteOffset' => 0, 'byteLength' => strlen($posiciones)],
                ['buffer' => 0, 'byteOffset' => strlen($posiciones), 'byteLength' => strlen($indices)],
            ],
            'buffers' => [['byteLength' => strlen($bin)]],
        ]);
        $json .= str_repeat(' ', (4 - strlen($json) % 4) % 4);

        $total = 12 + 8 + strlen($json) + 8 + strlen($bin);

        return 'glTF'.pack('V2', 2, $total)
            .pack('V', strlen($json)).'JSON'.$json
            .pack('V', strlen($bin))."BIN\0".$bin;
    }

    private function subir(string $contenido, string $nombre = 'modelo.glb')
    {
        $trozos = str_split($contenido, 65536);
        $id = $this->actingAs($this->equipo)->postJson(route('admin.projects.upload.init', $this->proyecto), [
            'file_type' => 'model_3d', 'original_name' => $nombre,
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

    public function test_el_glb_sale_comprimido_y_el_visor_pide_el_comprimido(): void
    {
        $original = $this->glb();
        $this->subir($original)->assertOk();

        $modelo = $this->proyecto->getFileByType('model_3d');
        $this->assertNotNull($modelo->variantes['draco'] ?? null, 'no salio la version Draco (esta gltf-pipeline en node_modules?)');

        $comprimido = Storage::get($modelo->variantes['draco']);
        $this->assertStringStartsWith('glTF', $comprimido, 'lo que salio no es un GLB');
        $this->assertStringContainsString('KHR_draco_mesh_compression', $comprimido);
        $this->assertLessThan(strlen($original), strlen($comprimido));

        // Y es lo que se sirve al visor, sin que el visor sepa nada.
        $url = $this->proyecto->urlDeFichero('model_3d');
        $this->assertStringContainsString('tam=draco', $url);
        $this->assertStringContainsString('f=modelo.glb', $url);

        $r = $this->get($url)->assertOk();
        $this->assertSame('"'.$modelo->version().'-draco"', $r->headers->get('ETag'));
    }

    public function test_un_fbx_se_sirve_tal_cual(): void
    {
        $this->subir('no es un glb pero da igual', 'modelo.fbx')->assertOk();

        $modelo = $this->proyecto->getFileByType('model_3d');
        $this->assertNull($modelo->variantes);
        $this->assertStringNotContainsString('tam=', $this->proyecto->urlDeFichero('model_3d'));
    }

    public function test_sin_la_herramienta_el_original_se_sirve_igual(): void
    {
        config(['ficheros.gltf_pipeline' => '/no/existe/gltf-pipeline']);

        $this->subir($this->glb())->assertOk();

        $modelo = $this->proyecto->getFileByType('model_3d');
        $this->assertNull($modelo->variantes);
        $this->get($this->proyecto->urlDeFichero('model_3d'))->assertOk();
    }

    public function test_si_la_herramienta_muere_escribiendo_no_queda_un_comprimido_a_medias(): void
    {
        // Una herramienta de mentira que escribe algo en el destino y falla:
        // como gltf-pipeline muriendo por tiempo o memoria a mitad.
        $falsa = sys_get_temp_dir().'/gltf-pipeline-que-muere-'.uniqid();
        file_put_contents($falsa, "#!/bin/sh\nprintf basura > \"\$4\"\nexit 1\n");
        chmod($falsa, 0755);
        config(['ficheros.gltf_pipeline' => $falsa]);

        try {
            $this->subir($this->glb())->assertOk();
        } finally {
            unlink($falsa);
        }

        $modelo = $this->proyecto->getFileByType('model_3d');
        $this->assertNull($modelo->variantes, 'se guardo como comprimido algo que la herramienta no termino');
        $this->assertCount(1, Storage::allFiles("projects/{$this->proyecto->id}/model"), 'quedo el fichero a medias en el disco');
    }

    public function test_un_glb_roto_no_deja_nada_a_medias(): void
    {
        $this->subir('glTF esto no es un glb de verdad')->assertOk();

        $modelo = $this->proyecto->getFileByType('model_3d');
        $this->assertNull($modelo->variantes);
        $this->assertCount(1, Storage::allFiles("projects/{$this->proyecto->id}/model"), 'quedo un fichero a medias');
    }

    public function test_lo_que_ya_estaba_subido_se_prepara_con_un_comando(): void
    {
        // Como quedan los ficheros subidos antes de esto: sin version ligera.
        Storage::put("projects/{$this->proyecto->id}/model/viejo.glb", $this->glb());
        ProjectFile::create([
            'project_id' => $this->proyecto->id,
            'file_type' => 'model_3d',
            'original_name' => 'viejo.glb',
            'storage_path' => "projects/{$this->proyecto->id}/model/viejo.glb",
            'mime_type' => 'model/gltf-binary',
            'file_size' => 100,
            'upload_complete' => true,
        ]);

        $this->artisan('visor:preparar', ['--ahora' => true])
            ->expectsOutputToContain('draco')
            ->assertSuccessful();

        $this->assertNotNull($this->proyecto->getFileByType('model_3d')->fresh()->variantes['draco'] ?? null);
    }
}
