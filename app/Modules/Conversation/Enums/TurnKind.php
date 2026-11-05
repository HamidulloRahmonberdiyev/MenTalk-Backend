<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Enums;

enum TurnKind: string
{
    case Opening = 'opening';
    case Text = 'text';
    case Audio = 'audio';
}
