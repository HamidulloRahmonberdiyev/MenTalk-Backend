<?php

declare(strict_types=1);

namespace App\Modules\Pronunciation\Enums;

enum PhraseLevel: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    /** What a line of this level looks like, as a prompt line. */
    public function rule(): string
    {
        return match ($this) {
            self::Easy => 'Short everyday phrases of 2 to 6 words, common vocabulary only.',
            self::Medium => 'Natural everyday sentences of 6 to 12 words.',
            self::Hard => 'Tongue twisters or long sentences of up to 20 words packed with the hardest sounds.',
        };
    }

    /** Word counts a generated line must fall within; a little looser than `rule()` asks. */
    public function wordRange(): array
    {
        return match ($this) {
            self::Easy => [1, 8],
            self::Medium => [4, 16],
            self::Hard => [4, 26],
        };
    }
}
