<?php

declare(strict_types=1);

namespace App\Enums;

/** Where a learner stands with a word; follows from the review schedule (`Srs`). */
enum WordStatus: string
{
    case New = 'new';
    case Learning = 'learning';
    case Known = 'known';
}
