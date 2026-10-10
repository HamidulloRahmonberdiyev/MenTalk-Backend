<?php

declare(strict_types=1);

namespace App\Support;

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
}
