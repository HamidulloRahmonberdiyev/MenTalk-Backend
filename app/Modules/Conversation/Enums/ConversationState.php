<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Enums;

enum ConversationState: string
{
    case Active = 'active';
    case Finished = 'finished';
}
