<?php

declare(strict_types=1);

namespace App\Enums;

/** Whether the learner's level was measured by the placement test or chosen by hand. */
enum LevelSource: string
{
    case Self = 'self';
    case Placement = 'placement';
}
