<?php

return [
    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'price_monthly_id' => env('STRIPE_PRICE_STARTER_MONTHLY'),
            'price_yearly_id' => env('STRIPE_PRICE_STARTER_YEARLY'),
            'price_monthly' => 49,
            'price_yearly' => 470,
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
