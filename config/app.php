<?php

/*
 * Seules les valeurs propres à Gestion sont définies ici. Les autres options
 * de configuration proviennent des valeurs par défaut de Laravel et du .env.
 */
return [
    'name' => env('APP_NAME', 'Gestion'),

    'timezone' => env('APP_TIMEZONE', 'Africa/Dakar'),

    'locale' => env('APP_LOCALE', 'fr'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'fr_FR'),
];
