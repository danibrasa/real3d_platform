<?php

return [
    'plans' => [
        // El plan de entrada es gratuito. Lo era ya en el codigo -- se activaba
        // sin pasar por Stripe -- pero la pagina anunciaba 49 USD al mes, asi
        // que quien lo elegia descubria que no le cobraban y quien lo leia se
        // iba antes de elegirlo. Un precio que no se cobra no es un precio: es
        // una barrera puesta por error.
        //
        // Lo que se lleva gratis es el producto entero menos el visor: una
        // promotora, sus viviendas, su pagina publicada y los contactos que
        // entren por ella, que son de verdad. El visor 3D lo montamos nosotros
        // y es lo que se paga, porque es lo unico que nos cuesta dinero hacer.
        'starter' => [
            'name' => 'Starter',
            'price_monthly_id' => null,
            'price_yearly_id' => null,
            'price_monthly' => 0,
            'price_yearly' => 0,
            'gratis' => true,
        ],
        'professional' => [
            'name' => 'Professional',
            'price_monthly_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'price_yearly_id' => env('STRIPE_PRICE_PRO_YEARLY'),
            'price_monthly' => 149,
            'price_yearly' => 1430,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly_id' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY'),
            'price_yearly_id' => env('STRIPE_PRICE_ENTERPRISE_YEARLY'),
            'price_monthly' => 399,
            'price_yearly' => 3830,
        ],
    ],
    'trial_days' => 14,
];
