<?php

declare(strict_types=1);

namespace App\Support;

final class Xp
{
    private const BASE = 10;

    private const PERFECT_BONUS = 20;

    private const MAX_COMBO_BONUS = 5;

    public static function forRound(array $corrects): int
    {
        $combo = 0;
        $xp = 0;

        foreach ($corrects as $correct) {
            $combo = $correct ? $combo + 1 : 0;
            $xp += $correct ? self::BASE + min(max($combo - 1, 0), self::MAX_COMBO_BONUS) * 2 : 0;
        }

        return $xp + (in_array(false, $corrects, true) || $corrects === [] ? 0 : self::PERFECT_BONUS);
    }
}
