<?php

return [
    'default' => env('APP_LOCALE', 'es'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'es'),
    'available' => [
        'es' => [
            'name' => 'Español',
            'native' => 'Español',
            'flag' => '🇪🇸',
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇺🇸',
        ],
    ],
    'future' => ['pt', 'fr', 'de'],
];
