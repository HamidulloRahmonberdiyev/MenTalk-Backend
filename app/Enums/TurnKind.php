<?php

declare(strict_types=1);

namespace App\Enums;

enum TurnKind: string
{
    case Opening = 'opening';
    case Text = 'text';
    case Audio = 'audio';
}
