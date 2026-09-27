<?php

namespace Tests\Unit;

use App\Support\Inversion\CalculoInversion;
use Tests\TestCase;

/**
 * Las cuentas que ve un inversor antes de transferir 185.000 dolares.
 *
 * Los numeros de referencia salen de la calculadora que ya existia en el
 * navegador: si estos tests se rompen al tocar algo, es que el PDF y la
 * pantalla habrian empezado a decir cosas distintas.
 */
class CalculoInversionTest extends TestCase
{
    /** Un caso corriente: 185.000 dolares, 185 la noche, 78% de ocupacion. */
    private function caso(float $precio = 185000): CalculoInversion
    {
        return new CalculoInversion(
            precio: $precio,
            precioNoche: 185,
            ocupacion: 78,
            comision: 20,
            impuestos: 1,
            revalorizacion: 12,
            anos: 5,
        );
    }

    public function test_ingreso_bruto_son_las_noches_ocupadas(): void
    {
        // 185 x 365 x 0,78
        $this->assertEqualsWithDelta(52669.5, $this->caso()->ingresoBruto(), 0.01);
    }

    public function test_la_gestion_se_lleva_su_porcentaje_del_bruto(): void
    {
        $this->assertEqualsWithDelta(10533.9, $this->caso()->costeGestion(), 0.01);
    }

    public function test_el_impuesto_va_sobre_el_valor_no_sobre_el_ingreso(): void
    {
        // Es un detalle que se confunde facil: 1% de 185.000, no del alquiler.
        $this->assertEqualsWithDelta(1850.0, $this->caso()->costeImpuestos(), 0.01);
    }

    public function test_el_neto_descuenta_gestion_e_impuestos(): void
    {
        // 52.669,50 - 10.533,90 - 1.850
        $this->assertEqualsWithDelta(40285.6, $this->caso()->ingresoNeto(), 0.01);
    }

    public function test_rentabilidad_sobre_lo_pagado(): void
    {
        // 40.285,60 / 185.000
        $this->assertEqualsWithDelta(21.78, $this->caso()->rentabilidad(), 0.01);
    }

    public function test_cuanto_tarda_en_recuperarse(): void
    {
        $this->assertEqualsWithDelta(4.59, $this->caso()->anosDeRetorno(), 0.01);
    }

    public function test_si_no_se_recupera_nunca_no_inventa_un_numero(): void
    {
        // Impuestos altisimos: el neto sale negativo y no hay retorno posible.
        $imposible = new CalculoInversion(
            precio: 185000, precioNoche: 50, ocupacion: 10,
            comision: 20, impuestos: 5, revalorizacion: 0,
        );

        $this->assertLessThan(0, $imposible->ingresoNeto());
        $this->assertNull($imposible->anosDeRetorno(), 'deberia decir que no se recupera');
    }

    public function test_el_valor_futuro_compone_la_revalorizacion(): void
    {
        // 185.000 x 1,12^5, no 185.000 x 1,60
        $this->assertEqualsWithDelta(326033.21, $this->caso()->valorFuturo(), 0.01);
    }

    public function test_la_ganancia_suma_alquiler_y_revalorizacion(): void
    {
        // 40.285,60 x 5 + (326.011 - 185.000)
        $this->assertEqualsWithDelta(342461.21, $this->caso()->gananciaTotal(), 0.01);
    }

    public function test_sin_datos_del_proyecto_avisa_en_vez_de_dar_ceros(): void
    {
        // Un proyecto sin precio por noche ni ocupacion no puede proyectar nada,
        // y un PDF lleno de ceros seria peor que no ofrecerlo.
        $vacio = new CalculoInversion(
            precio: 185000, precioNoche: 0, ocupacion: 0,
            comision: 0, impuestos: 0, revalorizacion: 0,
        );

        $this->assertFalse($vacio->hayDatos());
        $this->assertTrue($this->caso()->hayDatos());
    }

    public function test_un_precio_de_cero_no_revienta_la_rentabilidad(): void
    {
        $this->assertSame(0.0, $this->caso(0)->rentabilidad());
    }

    public function test_el_resumen_trae_todo_lo_que_pinta_el_pdf(): void
    {
        $r = $this->caso()->resumen();

        foreach (['precio', 'ingreso_bruto', 'coste_gestion', 'coste_impuestos',
            'ingreso_neto', 'ingreso_mensual', 'rentabilidad', 'anos_retorno',
            'valor_futuro', 'ganancia_total', 'anos', 'supuestos'] as $clave) {
            $this->assertArrayHasKey($clave, $r);
        }

        // Los supuestos viajan con el resultado: sin ellos, los numeros son
        // afirmaciones sin respaldo.
        $this->assertSame(185.0, $r['supuestos']['precio_noche']);
        $this->assertSame(78.0, $r['supuestos']['ocupacion']);
    }
}
