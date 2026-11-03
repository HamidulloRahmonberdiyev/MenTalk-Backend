<?php

declare(strict_types=1);

/*
 * The learning path: scenarios in their `sort` order form stages. A stage is passed with a
 * finished conversation scoring at least `pass_score` (0–10); passing opens the next one.
 * `stars` are the scores that earn one, two and three stars on a passed stage.
 */
return [
    'pass_score' => 6,
    'stars' => [6, 8, 9],
];
