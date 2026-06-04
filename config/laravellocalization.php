<?php

return [
    'supportedLocales' => [
        'es' => [
            'name' => 'Spanish',
            'script' => 'Latn',
            'native' => 'Español',
            'regional' => 'es_BO',
        ],
        'en' => [
            'name' => 'English',
            'script' => 'Latn',
            'native' => 'English',
            'regional' => 'en_US',
        ],
        'pt' => [
            'name' => 'Portuguese',
            'script' => 'Latn',
            'native' => 'Português',
            'regional' => 'pt_BR',
        ],
        'fr' => [
            'name' => 'French',
            'script' => 'Latn',
            'native' => 'Français',
            'regional' => 'fr_FR',
        ],
        'de' => [
            'name' => 'German',
            'script' => 'Latn',
            'native' => 'Deutsch',
            'regional' => 'de_DE',
        ],
    ],

    'useAcceptLanguageHeader' => true,
    'hideDefaultLocaleInURL' => false,
    'localesOrder' => ['es', 'en', 'pt', 'fr', 'de'],
    'localesMapping' => [],
    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),
    'urlsIgnored' => ['/admin', '/api'],
    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
