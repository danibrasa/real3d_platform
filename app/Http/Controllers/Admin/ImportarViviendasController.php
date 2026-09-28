<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitTypology;
use App\Support\Import\LectorDePdf;
use App\Support\Import\LectorDeViviendas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Cargar las viviendas de un proyecto desde un Excel o un CSV.
 *
 * Tres pasos: se sube el fichero, se revisa lo que el sistema ha entendido y se
 * confirma. El paso intermedio existe porque adivinar columnas nunca acierta
 * siempre, y es mejor enseñar lo que se va a crear que crearlo y que sorprenda.
 */
class ImportarViviendasController extends Controller
{
    /** Donde se guarda el fichero entre el paso 2 y el 3. */
    private const CARPETA = 'imports';

    public function __construct(private readonly LectorDeViviendas $lector) {}

    /** Paso 1: elegir fichero. */
    public function create(Project $project)
    {
        Gate::authorize('create-unit', $project);
        $this->autorizarProyecto($project);

        return view('admin.units.import.subir', compact('project'));
    }

    /** Paso 2: leer el fichero y enseñar lo que se ha entendido. */
    public function analizar(Request $request, Project $project)
    {
        Gate::authorize('create-unit', $project);
        $this->autorizarProyecto($project);

        $maxKb = max(10240, (int) config('importacion.max_mb', 15) * 1024);

        $request->validate([
            'fichero' => ['required', 'file', 'max:'.$maxKb, 'mimes:xlsx,csv,txt,ods,pdf'],
        ], [], ['fichero' => __('el fichero')]);

        $subido = $request->file('fichero');
        $nombre = Str::uuid().'.'.$subido->getClientOriginalExtension();
        $subido->storeAs(self::CARPETA, $nombre);

        $esPdf = strtolower($subido->getClientOriginalExtension()) === 'pdf';

        try {
            $leido = $esPdf
                ? $this->lectorPdf()->leer(Storage::path(self::CARPETA.'/'.$nombre))
                : $this->lector->leer(Storage::path(self::CARPETA.'/'.$nombre));
        } catch (\Throwable $e) {
            Storage::delete(self::CARPETA.'/'.$nombre);
            report($e);

            return back()->withErrors([
                'fichero' => $esPdf
                    ? __('No se ha podido leer el PDF. Prueba con el listado en Excel si lo tienes.')
                    : __('No se ha podido leer el fichero. Comprueba que sea un Excel o un CSV válido.'),
            ]);
        }

        if ($leido['total'] === 0) {
            Storage::delete(self::CARPETA.'/'.$nombre);

            return back()->withErrors([
                'fichero' => __('El fichero no tiene ninguna fila con datos.'),
            ]);
        }

        Storage::put(self::CARPETA.'/'.$nombre.'.json', json_encode($leido));

        session([
            'import_viviendas' => [
                'proyecto' => $project->id,
                'fichero' => $nombre,
                'original' => $subido->getClientOriginalName(),
                'pdf' => $esPdf,
            ],
        ]);

        return view('admin.units.import.revisar', [
            'project' => $project,
            'sinPrecio' => $this->cuantasSinPrecio($leido),
            'cabeceras' => $leido['cabeceras'],
            'mapeo' => $leido['mapeo'],
            'total' => $leido['total'],
            'original' => $subido->getClientOriginalName(),
            'campos' => LectorDeViviendas::camposDisponibles(),
            'vistaPrevia' => $this->vistaPrevia($leido),
            'existentes' => $project->units()->pluck('identifier')->all(),
        ]);
    }

    /** Paso 3: crear las viviendas. */
    public function confirmar(Request $request, Project $project)
    {
        Gate::authorize('create-unit', $project);
        $this->autorizarProyecto($project);

        $datos = $request->validate([
            'mapeo' => ['required', 'array'],
            'mapeo.*' => ['nullable', 'integer', 'min:0'],
            'duplicados' => ['required', 'in:saltar,actualizar'],
        ]);

        $sesion = session('import_viviendas');
        if (! $sesion || $sesion['proyecto'] !== $project->id) {
            return redirect()
                ->route('admin.projects.units.import.create', $project)
                ->withErrors(['fichero' => __('La importación ha caducado. Vuelve a subir el fichero.')]);
        }

        $ruta = Storage::path(self::CARPETA.'/'.$sesion['fichero']);
        if (! is_readable($ruta)) {
            return redirect()
                ->route('admin.projects.units.import.create', $project)
                ->withErrors(['fichero' => __('El fichero ya no está disponible. Vuelve a subirlo.')]);
        }

        if ($datos['mapeo']['identifier'] === null) {
            return back()->withErrors([
                'mapeo' => __('Hace falta indicar qué columna tiene el identificador de cada vivienda.'),
            ]);
        }

        // Se usa lo que se guardo al analizar, no una lectura nueva: lo que se
        // crea tiene que ser exactamente lo que la promotora vio y aprobo.
        $guardado = json_decode(Storage::get(self::CARPETA.'/'.$sesion['fichero'].'.json'), true);
        $leido = is_array($guardado) ? $guardado : $this->lector->leer($ruta);

        $resultado = $this->crearViviendas($project, $leido['filas'], $datos['mapeo'], $datos['duplicados']);

        Storage::delete(self::CARPETA.'/'.$sesion['fichero']);
        Storage::delete(self::CARPETA.'/'.$sesion['fichero'].'.json');
        session()->forget('import_viviendas');

        return redirect()
            ->route('admin.projects.units.index', $project)
            ->with('import_resultado', $resultado);
    }

    /**
     * Crea o actualiza las viviendas, fila a fila.
     *
     * Una fila mala no tumba la importación entera: se anota y se sigue. Es
     * preferible cargar 95 de 100 y enseñar los 5 fallos, que rechazar el
     * fichero completo por una celda vacía.
     *
     * @param  list<array<int, string>>  $filas
     * @param  array<string, int|null>  $mapeo
     */
    /**
     * Cuantas filas se quedarian sin precio.
     *
     * Se dice antes de crear nada porque cambia lo que vera un comprador: esas
     * viviendas apareceran como "consultar" en vez de con un precio. Un folleto
     * que pone "Consultar" en una unidad es lo mas normal del mundo, pero quien
     * revisa tiene que enterarse ahora y no cuando lo vea publicado.
     */
    private function cuantasSinPrecio(array $leido): int
    {
        $columna = $leido['mapeo']['price'] ?? null;
        if ($columna === null) {
            return $leido['total'];
        }

        $sin = 0;
        foreach ($leido['filas'] as $fila) {
            if ($this->lector->aDecimal($fila[$columna] ?? '') === null) {
                $sin++;
            }
        }

        return $sin;
    }

    private function lectorPdf(): LectorDePdf
    {
        $lector = new LectorDePdf(segundos: (int) config('importacion.segundos', 100));

        if (! config('importacion.pdf_habilitado', true) || ! $lector->disponible()) {
            throw new \RuntimeException('La lectura de PDFs no esta configurada.');
        }

        return $lector;
    }

    private function crearViviendas(Project $project, array $filas, array $mapeo, string $duplicados): array
    {
        $existentes = $project->units()->pluck('id', 'identifier')->all();
        $tipologias = $project->typologies()->pluck('id', 'name')->all();
        $hueco = $this->huecoDisponible($project, count($existentes));

        $creadas = $actualizadas = $saltadas = 0;
        $errores = [];
        $orden = $project->units()->max('sort_order') ?? 0;

        DB::transaction(function () use (
            $filas, $mapeo, $duplicados, $project, &$existentes, &$tipologias,
            &$creadas, &$actualizadas, &$saltadas, &$errores, &$orden, $hueco
        ) {
            foreach ($filas as $i => $fila) {
                $linea = $i + 2; // +1 por la cabecera, +1 porque se cuenta desde 1
                $v = $this->lector->interpretar($fila, $mapeo);

                if ($v['identifier'] === '') {
                    $errores[] = ['linea' => $linea, 'motivo' => __('sin identificador')];

                    continue;
                }

                $yaExiste = isset($existentes[$v['identifier']]);

                if ($yaExiste && $duplicados === 'saltar') {
                    $saltadas++;

                    continue;
                }

                if (! $yaExiste && $creadas >= $hueco) {
                    $errores[] = ['linea' => $linea, 'motivo' => __('se ha alcanzado el límite del plan')];

                    continue;
                }

                $campos = [
                    'floor' => $v['floor'] ?? 0,
                    'bedrooms' => $v['bedrooms'] ?? 0,
                    'bathrooms' => $v['bathrooms'] ?? 0,
                    'area_m2' => $v['area_m2'] ?? 0,
                    // Sin precio se guarda sin precio: en la web sale "consultar".
                    'price' => $v['price'],
                    'status' => $v['status'],
                    'notes' => $v['notes'],
                ];

                if ($v['typology']) {
                    $campos['typology_id'] = $tipologias[$v['typology']]
                        ??= UnitTypology::create([
                            'project_id' => $project->id,
                            'name' => $v['typology'],
                            'bedrooms' => $v['bedrooms'] ?? 0,
                            'bathrooms' => $v['bathrooms'] ?? 0,
                            'area_m2' => $v['area_m2'] ?? 0,
                        ])->id;
                }

                if ($yaExiste) {
                    Unit::where('id', $existentes[$v['identifier']])->update($campos);
                    $actualizadas++;

                    continue;
                }

                $unidad = Unit::create($campos + [
                    'project_id' => $project->id,
                    'identifier' => $v['identifier'],
                    'sort_order' => ++$orden,
                ]);
                $existentes[$v['identifier']] = $unidad->id;
                $creadas++;
            }
        });

        return [
            'creadas' => $creadas,
            'actualizadas' => $actualizadas,
            'saltadas' => $saltadas,
            'errores' => $errores,
        ];
    }

    /**
     * Cuantas viviendas nuevas caben todavia segun el plan contratado.
     *
     * El limite existia en PLAN_LIMITS pero no lo comprobaba nadie: creandolas
     * de una en una se notaba poco, pero un importador puede meter dos mil de
     * golpe.
     */
    private function huecoDisponible(Project $project, int $actuales): int
    {
        $perfil = auth()->user()->companyProfile ?? null;
        if (! $perfil) {
            return PHP_INT_MAX; // superadmin y gestor no tienen plan
        }

        $tope = $perfil->getPlanLimits()['max_units_per_project'] ?? PHP_INT_MAX;

        return max(0, $tope - $actuales);
    }

    /** Las primeras filas ya interpretadas, para que se vea que va a pasar. */
    private function vistaPrevia(array $leido): array
    {
        return array_map(
            fn ($fila) => $this->lector->interpretar($fila, $leido['mapeo']),
            array_slice($leido['filas'], 0, LectorDeViviendas::FILAS_VISTA_PREVIA)
        );
    }

    private function autorizarProyecto(Project $project): void
    {
        // Misma comprobacion que el resto del panel de viviendas.
        abort_unless(auth()->user()->canAccessProject($project), 403);
    }
}
