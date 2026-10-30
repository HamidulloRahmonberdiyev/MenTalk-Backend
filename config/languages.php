<?php

declare(strict_types=1);

return [
    // The language assumed when a client sends none: older app versions and records made before
    // the app taught more than one language were all Russian.
    'default' => env('DEFAULT_TARGET_LANGUAGE', 'ru'),
];
