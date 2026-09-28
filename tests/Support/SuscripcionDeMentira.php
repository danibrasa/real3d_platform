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
    ) {}

    public function canceled(): bool
    {
        return $this->cancelada;
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
