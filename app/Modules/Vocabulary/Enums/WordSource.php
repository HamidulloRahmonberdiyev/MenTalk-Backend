<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Enums;

/** How a word reached the learner's list. */
enum WordSource: string
{
    case AiChat = 'ai_chat';
    case Lesson = 'lesson';
    case Manual = 'manual';
}
