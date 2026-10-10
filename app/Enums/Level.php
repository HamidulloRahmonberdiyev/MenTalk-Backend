<?php

declare(strict_types=1);

namespace App\Enums;

enum Level: string
{
    case Beginner = 'beginner';
    case Elementary = 'elementary';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
}
