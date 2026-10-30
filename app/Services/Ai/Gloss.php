<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTO\TutorLanguage;
use App\Enums\InterfaceLanguage;

/** How a word of the taught language is explained to the learner, as prompt text. */
final class Gloss
{
    public static function for(InterfaceLanguage $interface, TutorLanguage $taught): string
    {
        // Translating into the language being learned would only repeat the word.
        return $interface->value === $taught->code
            ? "a very short, simple {$taught->name} explanation or synonym"
            : "{$interface->englishName()}, one to three words";
    }
}
