<?php

declare(strict_types=1);

// Read through config so the values survive `php artisan config:cache`.
return [
    'domain' => env('ADMIN_DOMAIN'),
    'path' => env('ADMIN_PATH', 'admin'),

    // First admin created by AdminSeeder. Leave the password out of version control.
    'seed' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
