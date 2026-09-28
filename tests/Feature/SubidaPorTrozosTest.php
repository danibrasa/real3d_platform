<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\UploadChunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La subida por trozos, que es como el equipo monta el visor.
 *
 * Es el camino mas complejo del producto y no tenia ni un test: los videos 360
 * pesan cientos de megas y van en trozos de 5 MB que luego se pegan. Si eso se
 * rompe, ningun proyecto llega a publicarse y nadie se entera hasta que una
 * promotora lo pregunta.
 *
 * En produccion hay 28 subidas registradas y 12 sin completar desde febrero.
 */
class SubidaPorTrozosTest extends TestCase
{
    use RefreshDatabase;

    private User $equipo;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->equipo = User::factory()->create(['role' => 'superadmin']);

        $promotora = User::factory()->create(['role' => 'inmobiliaria']);
        CompanyProfile::create([
            'user_id' => $promotora->id,
            'company_name' => 'Promotora',
            'slug' => 'promotora',
            'plan_tier' => CompanyProfile::PLAN_PROFESSIONAL,
            'max_projects' => 5,
            'max_storage_bytes' => 100 * 1024 * 1024,
        ]);

        $this->proyecto = Project::create([
            'name' => 'Residencial', 'slug' => 'residencial',
            'status' => 'draft', 'created_by' => $promotora->id,
        ]);
        $promotora->assignedProjects()->attach($this->proyecto->id);
    }

    /** Sube un fichero partido en trozos, como hace el navegador. */
    private function subir(string $contenido, int $tamañoTrozo = 8, string $tipo = 'model_3d'): array
    {
        $trozos = str_split($contenido, $tamañoTrozo);

        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            [
                'file_type' => $tipo,
                'original_name' => 'modelo.glb',
                'total_size' => strlen($contenido),
                'total_chunks' => count($trozos),
            ]
        );

        $id = $r->json('upload_id');

        foreach ($trozos as $i => $trozo) {
            $this->actingAs($this->equipo)->post(
                route('admin.projects.upload.chunk', $this->proyecto),
                [
                    'upload_id' => $id,
                    'chunk_index' => $i,
                    'chunk' => UploadedFile::fake()->createWithContent("t{$i}", $trozo),
                ]
            );
        }

        $final = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.complete', $this->proyecto),
            ['upload_id' => $id]
        );

        return ['id' => $id, 'respuesta' => $final, 'trozos' => count($trozos)];
    }

    // --- El camino que tiene que funcionar --------------------------------

    public function test_el_fichero_se_reconstruye_identico(): void
    {
        // Lo unico que de verdad importa: que lo que llega sea lo que se subio.
        // Un modelo 3D con un byte cambiado no carga.
        $contenido = random_bytes(200);

        $r = $this->subir($contenido, 8);
        $r['respuesta']->assertOk();

        $fichero = ProjectFile::where('project_id', $this->proyecto->id)->firstOrFail();

        $this->assertSame($contenido, Storage::get($fichero->storage_path));
        $this->assertSame(strlen($contenido), $fichero->file_size);
        $this->assertTrue($fichero->upload_complete);
    }

    public function test_un_solo_trozo_tambien_vale(): void
    {
        $r = $this->subir('un fichero pequeño', 1000);

        $r['respuesta']->assertOk();
        $this->assertSame(1, $r['trozos']);
        $this->assertDatabaseCount('project_files', 1);
    }

    public function test_los_trozos_temporales_se_borran_al_terminar(): void
    {
        $r = $this->subir(random_bytes(100), 8);

        $upload = UploadChunk::where('upload_id', $r['id'])->firstOrFail();

        $this->assertTrue($upload->completed);
        $this->assertEmpty(Storage::allFiles($upload->temp_directory));
    }

    public function test_subir_otra_vez_reemplaza_el_anterior(): void
    {
        // El equipo corrige un modelo y lo vuelve a subir: no deben quedar dos.
        $this->subir('primera version', 100);
        $this->subir('segunda version', 100);

        $this->assertDatabaseCount('project_files', 1);
        $fichero = ProjectFile::firstOrFail();
        $this->assertSame('segunda version', Storage::get($fichero->storage_path));
    }

    // --- Lo que no debe pasar ---------------------------------------------

    public function test_si_falta_un_trozo_no_se_crea_el_fichero(): void
    {
        // Peor que fallar seria crear un modelo truncado y darlo por bueno.
        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'm.glb',
                'total_size' => 100, 'total_chunks' => 3]
        );
        $id = $r->json('upload_id');

        // Solo llega el primero de los tres.
        $this->actingAs($this->equipo)->post(
            route('admin.projects.upload.chunk', $this->proyecto),
            ['upload_id' => $id, 'chunk_index' => 0,
                'chunk' => UploadedFile::fake()->createWithContent('t0', 'aaa')]
        );

        $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.complete', $this->proyecto),
            ['upload_id' => $id]
        )->assertStatus(422);

        $this->assertDatabaseCount('project_files', 0);
    }

    public function test_una_promotora_no_sube_el_modelo_3d(): void
    {
        // El reparto: el 3D lo monta el equipo.
        $promotora = $this->proyecto->assignedAgencies()->first();

        $this->actingAs($promotora)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'm.glb',
                'total_size' => 10, 'total_chunks' => 1]
        )->assertForbidden();
    }

    public function test_no_se_pueden_meter_trozos_en_la_subida_de_otro_proyecto(): void
    {
        $otro = Project::create([
            'name' => 'Otro', 'slug' => 'otro', 'status' => 'draft',
            'created_by' => $this->equipo->id,
        ]);

        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'm.glb',
                'total_size' => 10, 'total_chunks' => 1]
        );

        $this->actingAs($this->equipo)->post(
            route('admin.projects.upload.chunk', $otro),
            ['upload_id' => $r->json('upload_id'), 'chunk_index' => 0,
                'chunk' => UploadedFile::fake()->createWithContent('t', 'x')]
        )->assertNotFound();
    }

    public function test_no_se_deja_subir_lo_que_no_va_a_caber(): void
    {
        // Mejor decirlo al empezar que despues de trescientos megas.
        $perfil = $this->proyecto->assignedAgencies()->first()->companyProfile;
        $perfil->update(['max_storage_bytes' => 100]);

        $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'm.glb',
                'total_size' => 5000, 'total_chunks' => 1]
        )->assertStatus(403);

        $this->assertDatabaseCount('upload_chunks', 0);
    }

    public function test_si_la_cuota_se_agota_por_el_camino_no_queda_basura(): void
    {
        // La comprobacion del final sigue haciendo falta: entre empezar y
        // terminar pueden haberse subido otros ficheros. Y cuando rechaza, los
        // trozos tienen que irse: sin eso, cada reintento dejaba el fichero
        // entero en disco sin que nada lo recogiera.
        $perfil = $this->proyecto->assignedAgencies()->first()->companyProfile;

        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'm.glb',
                'total_size' => 5000, 'total_chunks' => 1]
        );
        $id = $r->json('upload_id');

        $this->actingAs($this->equipo)->post(
            route('admin.projects.upload.chunk', $this->proyecto),
            ['upload_id' => $id, 'chunk_index' => 0,
                'chunk' => UploadedFile::fake()->createWithContent('t', str_repeat('x', 5000))]
        );

        // Mientras subia, la cuota se lleno con otra cosa.
        $perfil->update(['max_storage_bytes' => 10]);

        $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.complete', $this->proyecto),
            ['upload_id' => $id]
        )->assertStatus(403);

        $upload = UploadChunk::where('upload_id', $id)->firstOrFail();
        $this->assertEmpty(
            Storage::allFiles($upload->temp_directory),
            'Los trozos de una subida rechazada tienen que borrarse'
        );
        $this->assertDatabaseCount('project_files', 0);
    }

    // --- Reanudar lo que se corto -----------------------------------------

    /** Empieza una subida y manda solo algunos trozos, como una conexion que se cae. */
    private function empezarYCortar(string $contenido, int $tamañoTrozo, int $cuantos): array
    {
        $trozos = str_split($contenido, $tamañoTrozo);

        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'modelo.glb',
                'total_size' => strlen($contenido), 'total_chunks' => count($trozos)]
        );

        $id = $r->json('upload_id');

        foreach (array_slice($trozos, 0, $cuantos, true) as $i => $trozo) {
            $this->actingAs($this->equipo)->post(
                route('admin.projects.upload.chunk', $this->proyecto),
                ['upload_id' => $id, 'chunk_index' => $i,
                    'chunk' => UploadedFile::fake()->createWithContent("t{$i}", $trozo)]
            );
        }

        return ['id' => $id, 'trozos' => $trozos];
    }

    public function test_una_subida_cortada_se_reanuda(): void
    {
        // El caso que explica que en produccion 12 de 28 subidas nunca acabaran:
        // se cae la conexion y hasta ahora habia que subirlo entero otra vez.
        $contenido = random_bytes(80);
        $primera = $this->empezarYCortar($contenido, 10, 5);

        // El navegador lo vuelve a intentar: init debe reconocer lo que ya hay.
        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'modelo.glb',
                'total_size' => strlen($contenido), 'total_chunks' => 8]
        );

        $this->assertSame($primera['id'], $r->json('upload_id'), 'Tiene que continuar la misma subida');
        $this->assertSame([0, 1, 2, 3, 4], $r->json('trozos_ya_subidos'));
    }

    public function test_reanudada_el_fichero_sale_igual_de_bien(): void
    {
        $contenido = random_bytes(80);
        $primera = $this->empezarYCortar($contenido, 10, 5);

        // Se mandan solo los que faltaban.
        foreach (array_slice($primera['trozos'], 5, null, true) as $i => $trozo) {
            $this->actingAs($this->equipo)->post(
                route('admin.projects.upload.chunk', $this->proyecto),
                ['upload_id' => $primera['id'], 'chunk_index' => $i,
                    'chunk' => UploadedFile::fake()->createWithContent("t{$i}", $trozo)]
            );
        }

        $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.complete', $this->proyecto),
            ['upload_id' => $primera['id']]
        )->assertOk();

        $fichero = ProjectFile::firstOrFail();
        $this->assertSame($contenido, Storage::get($fichero->storage_path));
    }

    public function test_otro_fichero_distinto_empieza_de_cero(): void
    {
        // Reanudar solo vale para el mismo fichero: si cambia el tamaño o el
        // nombre, los trozos de antes no valen y mezclarlos daria un fichero
        // corrupto que ademas parecia correcto.
        $this->empezarYCortar(random_bytes(80), 10, 5);

        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'otro.glb',
                'total_size' => 80, 'total_chunks' => 8]
        );

        $this->assertSame([], $r->json('trozos_ya_subidos'));
    }

    public function test_el_contador_no_se_pasa_con_los_reintentos(): void
    {
        // El navegador reintenta un trozo hasta tres veces. Con increment() cada
        // reintento sumaba uno y el contador acababa por encima del total, que
        // es lo que ve el usuario como progreso.
        $r = $this->actingAs($this->equipo)->postJson(
            route('admin.projects.upload.init', $this->proyecto),
            ['file_type' => 'model_3d', 'original_name' => 'm.glb',
                'total_size' => 30, 'total_chunks' => 3]
        );
        $id = $r->json('upload_id');

        foreach ([0, 0, 0] as $i) {
            $this->actingAs($this->equipo)->post(
                route('admin.projects.upload.chunk', $this->proyecto),
                ['upload_id' => $id, 'chunk_index' => $i,
                    'chunk' => UploadedFile::fake()->createWithContent('t', 'aaaaaaaaaa')]
            );
        }

        $this->assertSame(1, UploadChunk::where('upload_id', $id)->value('received_chunks'));
    }
}
