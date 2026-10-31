<?php

declare(strict_types=1);

namespace App\Enums;

/** What the learner finds hardest and wants the tutor to work on. */
enum Focus: string
{
    case Speaking = 'speaking';
    case Listening = 'listening';
    case Vocabulary = 'vocabulary';
    case Grammar = 'grammar';
    case Pronunciation = 'pronunciation';
    case Confidence = 'confidence';
}
