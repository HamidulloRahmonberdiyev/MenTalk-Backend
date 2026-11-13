<?php

declare(strict_types=1);

namespace App\Modules\Progress\Enums;

/** Which XP the leaderboard ranks by. */
enum LeaderboardPeriod: string
{
    /** XP earned since Monday, so the board starts over every week and newcomers can still climb it. */
    case Week = 'week';

    /** Every point ever earned. */
    case All = 'all';
}
