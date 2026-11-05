<?php

declare(strict_types=1);

namespace App\Modules\Account\Enums;

enum Level: string
{
    case Beginner = 'beginner';
    case Elementary = 'elementary';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** The CEFR band the app's own levels correspond to. */
    public function cefr(): string
    {
        return match ($this) {
            self::Beginner => 'A1',
            self::Elementary => 'A2',
            self::Intermediate => 'B1',
            self::Advanced => 'B2+',
        };
    }
}
