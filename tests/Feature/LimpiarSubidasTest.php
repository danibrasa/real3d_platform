<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\UploadChunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Recoger las subidas que se quedaron a medias.
 *
 * Un video 360 se sube en sesenta trozos; si se cierra el navegador en el
 * cuarenta, los cuarenta primeros se quedan en disco y no hay ninguna pantalla
 * donde alguien vaya a verlos. En produccion habia 12 asi desde febrero.
 */
class LimpiarSubidasTest extends TestCase
{
    use RefreshDatabase;

    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $autor = User::factory()->create(['role' => 'superadmin']);
        $this->proyecto = Project::create([
            'name' => 'Residencial', 'slug' => 'residencial',
            'status' => 'draft', 'created_by' => $autor->id,
        ]);
    }

    private function subida(bool $completada, int $horas, string $nombre = 'm.glb'): UploadChunk
    {
        $id = (string) Str::uuid();
        $dir = "uploads/chunks/{$id}";

        Storage::put($dir.'/chunk_0', str_repeat('x', 1024));

        $u = UploadChunk::create([
            'upload_id' => $id,
            'project_id' => $this->proyecto->id,
            'file_type' => 'model_3d',
            'original_name' => $nombre,
            'total_size' => 1024,
            'total_chunks' => 3,
            'received_chunks' => 1,
            'temp_directory' => $dir,
            'completed' => $completada,
        ]);

        // created_at no se deja poner en el create porque lo pisa el modelo.
        $u->forceFill(['created_at' => now()->subHours($horas)])->save();

        return $u;
    }

    public function test_borra_lo_que_lleva_parado_mas_de_un_dia(): void
    {
        $vieja = $this->subida(completada: false, horas: 48);

        $this->artisan('subidas:limpiar')->assertSuccessful();

        $this->assertDatabaseMissing('upload_chunks', ['id' => $vieja->id]);
        $this->assertEmpty(Storage::allFiles($vieja->temp_directory));
    }

    public function test_no_toca_una_subida_en_marcha(): void
    {
        // Alguien puede estar subiendo trescientos megas ahora mismo.
        $enCurso = $this->subida(completada: false, horas: 1);

        $this->artisan('subidas:limpiar')->assertSuccessful();

        $this->assertDatabaseHas('upload_chunks', ['id' => $enCurso->id]);
        $this->assertNotEmpty(Storage::allFiles($enCurso->temp_directory));
    }

    public function test_no_toca_las_que_terminaron_bien(): void
    {
        $hecha = $this->subida(completada: true, horas: 100);

        $this->artisan('subidas:limpiar')->assertSuccessful();

        $this->assertDatabaseHas('upload_chunks', ['id' => $hecha->id]);
    }

    public function test_probar_no_borra_nada(): void
    {
        $vieja = $this->subida(completada: false, horas: 48);

        $this->artisan('subidas:limpiar --probar')->assertSuccessful();

        $this->assertDatabaseHas('upload_chunks', ['id' => $vieja->id]);
        $this->assertNotEmpty(Storage::allFiles($vieja->temp_directory));
    }

    public function test_se_puede_ajustar_cuanto_espera(): void
    {
        $deSeisHoras = $this->subida(completada: false, horas: 6);

        $this->artisan('subidas:limpiar --horas=2')->assertSuccessful();

        $this->assertDatabaseMissing('upload_chunks', ['id' => $deSeisHoras->id]);
    }
}
