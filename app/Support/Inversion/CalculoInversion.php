<?php

namespace App\Support\Inversion;

use App\Models\Project;
use App\Models\Unit;

/**
 * Las cuentas de una inversion en alquiler vacacional.
 *
 * Estaban solo en el JavaScript de la calculadora. Al llevarlas aqui, el PDF,
 * la API y la propia calculadora parten de la misma formula: si divergieran, el
 * comprador veria un numero en pantalla y otro en el papel que se lleva.
 *
 * Todo son estimaciones a partir de los datos que el promotor declara del
 * proyecto. No son una promesa de rentabilidad, y el PDF lo dice.
 */
class CalculoInversion
{
    /** Anos de proyeccion por defecto, los mismos que la calculadora. */
    public const ANOS_POR_DEFECTO = 5;

    public function __construct(
        private readonly float $precio,
        private readonly float $precioNoche,
        private readonly float $ocupacion,      // %
        private readonly float $comision,       // % de gestion sobre el ingreso bruto
        private readonly float $impuestos,      // % anual sobre el valor
        private readonly float $revalorizacion, // % anual
        private readonly int $anos = self::ANOS_POR_DEFECTO,
    ) {}

    /** Construye el calculo a partir de una vivienda y su proyecto. */
    public static function paraVivienda(Unit $unit, ?Project $project = null, ?int $anos = null): self
    {
        $p = $project ?? $unit->project;

        return new self(
            precio: (float) $unit->price,
            precioNoche: (float) ($p->avg_nightly_rate ?? 0),
            ocupacion: (float) ($p->average_occupancy ?? 0),
            comision: (float) ($p->management_fee ?? 0),
            impuestos: (float) ($p->property_tax_rate ?? 0),
            revalorizacion: (float) ($p->appreciation_rate_annual ?? 0),
            anos: $anos ?? self::ANOS_POR_DEFECTO,
        );
    }

    /** Si el proyecto no declara datos de alquiler, no hay nada que calcular. */
    public function hayDatos(): bool
    {
        return $this->precio > 0 && $this->precioNoche > 0 && $this->ocupacion > 0;
    }

    /** Ingreso bruto de un ano, antes de gastos. */
    public function ingresoBruto(): float
    {
        return $this->precioNoche * 365 * ($this->ocupacion / 100);
    }

    /** Lo que se lleva quien gestiona el alquiler. */
    public function costeGestion(): float
    {
        return $this->ingresoBruto() * ($this->comision / 100);
    }

    /** Impuesto anual sobre el valor del inmueble. */
    public function costeImpuestos(): float
    {
        return $this->precio * ($this->impuestos / 100);
    }

    /** Lo que queda limpio en un ano. */
    public function ingresoNeto(): float
    {
        return $this->ingresoBruto() - $this->costeGestion() - $this->costeImpuestos();
    }

    public function ingresoMensual(): float
    {
        return $this->ingresoNeto() / 12;
    }

    /** Rentabilidad anual sobre el precio pagado, en porcentaje. */
    public function rentabilidad(): float
    {
        return $this->precio > 0 ? $this->ingresoNeto() / $this->precio * 100 : 0.0;
    }

    /**
     * Anos hasta recuperar lo invertido solo con el alquiler.
     *
     * Devuelve null si el neto no es positivo: ahi no se recupera nunca, y un
     * numero enorme confundiria mas que ayudar.
     */
    public function anosDeRetorno(): ?float
    {
        $neto = $this->ingresoNeto();

        return $neto > 0 ? $this->precio / $neto : null;
    }

    /** Lo que valdria el inmueble al final de la proyeccion. */
    public function valorFuturo(): float
    {
        return $this->precio * pow(1 + $this->revalorizacion / 100, $this->anos);
    }

    /** Alquiler acumulado mas la revalorizacion, en el periodo proyectado. */
    public function gananciaTotal(): float
    {
        return $this->ingresoNeto() * $this->anos + ($this->valorFuturo() - $this->precio);
    }

    public function anos(): int
    {
        return $this->anos;
    }

    public function precio(): float
    {
        return $this->precio;
    }

    /** Los datos declarados, para poder enseñar de donde sale cada numero. */
    public function supuestos(): array
    {
        return [
            'precio_noche' => $this->precioNoche,
            'ocupacion' => $this->ocupacion,
            'comision' => $this->comision,
            'impuestos' => $this->impuestos,
            'revalorizacion' => $this->revalorizacion,
        ];
    }

    /** Todo junto, listo para una plantilla o una respuesta JSON. */
    public function resumen(): array
    {
        return [
            'precio' => round($this->precio, 2),
            'ingreso_bruto' => round($this->ingresoBruto(), 2),
            'coste_gestion' => round($this->costeGestion(), 2),
            'coste_impuestos' => round($this->costeImpuestos(), 2),
            'ingreso_neto' => round($this->ingresoNeto(), 2),
            'ingreso_mensual' => round($this->ingresoMensual(), 2),
            'rentabilidad' => round($this->rentabilidad(), 2),
            'anos_retorno' => $this->anosDeRetorno() !== null ? round($this->anosDeRetorno(), 1) : null,
            'valor_futuro' => round($this->valorFuturo(), 2),
            'ganancia_total' => round($this->gananciaTotal(), 2),
            'anos' => $this->anos,
            'supuestos' => $this->supuestos(),
        ];
    }
}
