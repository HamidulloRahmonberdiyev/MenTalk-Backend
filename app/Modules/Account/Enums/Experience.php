<?php

declare(strict_types=1);

namespace App\Modules\Account\Enums;

/** How the learner studied the language before joining. */
enum Experience: string
{
    case None = 'none';
    case School = 'school';
    case SelfTaught = 'self_taught';
    case Courses = 'courses';
    case Immersion = 'immersion';

    public function describe(): string
    {
        return match ($this) {
            self::None => 'has never studied the language',
            self::School => 'studied it at school',
            self::SelfTaught => 'taught themselves with apps and videos',
            self::Courses => 'took courses or had a tutor',
            self::Immersion => 'picked it up from living or working among speakers',
        };
    }
}
