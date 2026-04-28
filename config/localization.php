<?php

return [
    'supported_locales' => [
        'tr' => 'Turkce',
        'en' => 'English',
    ],

    'default_locale' => env('APP_LOCALE', 'tr'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'session_key' => 'locale',
];
