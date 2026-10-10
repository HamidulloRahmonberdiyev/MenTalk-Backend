<?php

declare(strict_types=1);

namespace App\Enums;

enum ConversationState: string
{
    case Active = 'active';
    case Finished = 'finished';
}
