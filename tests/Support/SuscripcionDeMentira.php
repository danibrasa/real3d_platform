<?php

namespace Tests\Support;

use Illuminate\Support\Carbon;

/**
 * Una suscripcion que apunta lo que le piden en vez de hablar con Stripe.
 *
 * Existe para poder comprobar tres cosas que solo se podian afirmar leyendo el
 * codigo: que se llama a extendTrial, con que fecha, y que la marca en la base
 * de datos no se pone cuando la pasarela falla.
 */
class SuscripcionDeMentira
{
    public ?Carbon $pedida = null;

    public int $veces = 0;

    public function __construct(
        private bool $cancelada = false,
        private bool $revienta = false,
        private bool $enPrueba = true,
    ) {}

    public function canceled(): bool
    {
        return $this->cancelada;
    }

    /**
     * Existe para poder simular la espera larga: cuando montamos el visor mas
     * tarde de lo que duraba la prueba original, esto ya es falso. El codigo no
     * debe mirarlo -- mirarlo era el fallo -- y el test lo comprueba poniendolo
     * en falso y exigiendo que se ancle igual.
     */
    public function onTrial(): bool
    {
        return $this->enPrueba;
    }

    public function extendTrial(Carbon $fecha): void
    {
        $this->veces++;

        if ($this->revienta) {
            throw new \RuntimeException('Stripe no contesta');
        }

        $this->pedida = $fecha;
    }
}
