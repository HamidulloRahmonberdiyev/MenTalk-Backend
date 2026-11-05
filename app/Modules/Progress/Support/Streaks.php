<?php

declare(strict_types=1);

namespace App\Modules\Progress\Support;

use Carbon\CarbonImmutable;

final class Streaks
{
    public static function next(int $streak, ?CarbonImmutable $lastActive, CarbonImmutable $today): int
    {
        return match (true) {
            $lastActive?->isSameDay($today) => max($streak, 1),
            $lastActive?->isSameDay($today->subDay()) => $streak + 1,
            default => 1,
        };
    }

    public static function isMilestone(int $streak): bool
    {
        return in_array($streak, config('notifications.streak_milestones'), true);
    }
}
