<?php

declare(strict_types=1);

namespace App\Enums;

enum TurnRole: string
{
    case Learner = 'learner';
    case Tutor = 'tutor';

    public function geminiRole(): string
    {
        return $this === self::Tutor ? 'model' : 'user';
    }
}
