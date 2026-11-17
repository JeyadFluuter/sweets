<?php

return [
    'driver' => env('PAYMENT_DRIVER', 'manual'),

    'drivers' => [
        'manual' => [
            'label' => 'دفع يدوي',
        ],
        'fake' => [
            'label' => 'بوابة تجريبية',
            'auto_confirm' => true,
        ],
    ],

    'advance_plans' => [25, 50, 100],

    'webhook' => [
        'secret' => env('PAYMENT_WEBHOOK_SECRET'),
    ],
];
