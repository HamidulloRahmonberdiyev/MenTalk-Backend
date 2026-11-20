<?php

declare(strict_types=1);

namespace App\Modules\Speech\Enums;

enum VoiceGender: string
{
    case Female = 'female';
    case Male = 'male';

    /** As the admin panel shows it. */
    public function label(): string
    {
        return match ($this) {
            self::Female => 'Ayol',
            self::Male => 'Erkak',
        };
    }
}
