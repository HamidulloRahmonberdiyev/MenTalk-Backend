<?php

declare(strict_types=1);

namespace App\Enums;

/** How well a word was recalled, on the usual four-button scale. */
enum ReviewRating: int
{
    case Again = 1;
    case Hard = 2;
    case Good = 3;
    case Easy = 4;

    /** Games only know right or wrong. */
    public static function fromCorrect(bool $correct): self
    {
        return $correct ? self::Good : self::Again;
    }

    public function recalled(): bool
    {
        return $this !== self::Again;
    }
}
