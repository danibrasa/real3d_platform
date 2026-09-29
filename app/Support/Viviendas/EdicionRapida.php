<?php

namespace App\Support\Viviendas;

use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Validator;

/**
 * Precio y estado de muchas viviendas de una vez, desde la tabla.
 *
 * Cambiar sesenta precios entrando en sesenta formularios es una tarde; en la
 * tabla, con un solo "guardar", es un minuto. Cada fila se juzga sola: la que
 * va mal se explica con su identificador y las demas se guardan igual. Lo que
 * no cambia no se toca (ni updated_at ni webhook), asi que enviar la tabla
 * entera no hace nada a lo que no se edito.
 */
class EdicionRapida
{
    public const ESTADOS = ['available', 'reserved', 'sold'];

    /** @var array<int, true> ids: una vivienda editada en su fila y en el lote cuenta una vez */
    private array $guardadas = [];

    /** @var array<int, true> */
    private array $sinCambios = [];

    /** @var array<int, array{vivienda: string, motivo: string}> */
    private array $errores = [];

    public function __construct(private Project $proyecto, private User $usuario) {}

    /**
     * Las filas editadas: id de vivienda => ['price' => ..., 'status' => ...].
     * Solo se mira lo que viene; una vivienda de otro proyecto se ignora.
     */
    public function filas(array $filas): self
    {
        $viviendas = $this->proyecto->units()->whereIn('id', array_keys($filas))->get()->keyBy('id');

        foreach ($filas as $id => $campos) {
            $vivienda = $viviendas->get((int) $id);
            if ($vivienda) {
                $this->guardar($vivienda, is_array($campos) ? $campos : []);
            }
        }

        return $this;
    }

    /** El mismo estado para todas las marcadas. */
    public function estadoEnLote(array $ids, string $estado): self
    {
        foreach ($this->proyecto->units()->whereIn('id', $ids)->get() as $vivienda) {
            $this->guardar($vivienda, ['status' => $estado]);
        }

        return $this;
    }

    /** Subir o bajar el precio de las marcadas un porcentaje; sin precio no hay porcentaje. */
    public function porcentajeEnLote(array $ids, float $porcentaje): self
    {
        foreach ($this->proyecto->units()->whereIn('id', $ids)->get() as $vivienda) {
            if ($vivienda->price === null) {
                $this->error($vivienda, 'no tiene precio, y un porcentaje de nada es nada');

                continue;
            }
            $this->guardar($vivienda, ['price' => round($vivienda->price * (1 + $porcentaje / 100), 2)]);
        }

        return $this;
    }

    private function guardar(Unit $vivienda, array $campos): void
    {
        $cambios = [];

        if (array_key_exists('price', $campos)) {
            $precio = $this->precio($vivienda, $campos['price']);
            if ($precio === false) {
                return;
            }
            if ($precio !== $vivienda->price) {
                $cambios['price'] = $precio;
            }
        }

        if (array_key_exists('status', $campos)) {
            $estado = $this->estado($vivienda, $campos['status']);
            if ($estado === false) {
                return;
            }
            if ($estado !== $vivienda->status) {
                $cambios['status'] = $estado;
            }
        }

        if (! $cambios) {
            $this->sinCambios[$vivienda->id] = true;

            return;
        }

        $estadoAnterior = $vivienda->status;
        $vivienda->update($cambios);
        $this->guardadas[$vivienda->id] = true;

        if (isset($cambios['status'])) {
            WebhookService::dispatch('unit_status_changed', [
                'unit_identifier' => $vivienda->identifier,
                'old_status' => $estadoAnterior,
                'new_status' => $vivienda->status,
            ], $this->proyecto->id);
        }
    }

    /** El precio como float, null si viene vacio, o false (ya explicado) si no vale. */
    private function precio(Unit $vivienda, mixed $valor): float|null|false
    {
        $texto = is_string($valor) ? trim($valor) : $valor;
        $precio = ($texto === '' || $texto === null) ? null : $texto;

        if ($precio !== null) {
            if (Validator::make(['p' => $precio], ['p' => 'numeric|min:0|max:99999999.99'])->fails()) {
                $this->error($vivienda, 'el precio no es un número válido');

                return false;
            }
            $precio = (float) $precio;
        }

        // El agente no toca precios: su tabla los enseña sin dejar editarlos, y
        // si aun asi llega uno distinto, se dice en vez de guardarlo callado.
        if ($this->usuario->isAgente() && $precio !== $vivienda->price) {
            $this->error($vivienda, 'los agentes no cambian precios');

            return false;
        }

        return $precio;
    }

    private function estado(Unit $vivienda, mixed $valor): string|false
    {
        if (! is_string($valor) || ! in_array($valor, self::ESTADOS, true)) {
            $this->error($vivienda, 'el estado no es uno de los posibles');

            return false;
        }

        if ($this->usuario->isAgente() && $valor !== 'reserved' && $valor !== $vivienda->status) {
            $this->error($vivienda, 'los agentes solo pueden reservar');

            return false;
        }

        return $valor;
    }

    private function error(Unit $vivienda, string $motivo): void
    {
        $this->errores[] = ['vivienda' => $vivienda->identifier, 'motivo' => $motivo];
    }

    /** @return array{guardadas: int, sin_cambios: int, errores: array} */
    public function resumen(): array
    {
        return [
            'guardadas' => count($this->guardadas),
            'sin_cambios' => count(array_diff_key($this->sinCambios, $this->guardadas)),
            'errores' => $this->errores,
        ];
    }
}
