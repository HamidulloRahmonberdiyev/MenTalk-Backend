<?php

declare(strict_types=1);

namespace App\Modules\Progress\Support;

final class Xp
{
    private const BASE = 10;

    private const PERFECT_BONUS = 20;

    private const MAX_COMBO_BONUS = 5;

    /** Fewer replies than this is a peek, not a conversation, and must not farm the leaderboard. */
    private const CONVERSATION_MIN_TURNS = 3;

    private const CONVERSATION_BASE = 10;

    private const CONVERSATION_PER_SCORE_POINT = 2;

    /** A finished talk: a flat share for taking part, plus more the better it went (score 0–10, so 10–30 XP). */
    public static function forConversation(int $learnerTurns, int $overallScore): int
    {
        if ($learnerTurns < self::CONVERSATION_MIN_TURNS) {
            return 0;
        }

        return self::CONVERSATION_BASE + max(0, min(10, $overallScore)) * self::CONVERSATION_PER_SCORE_POINT;
    }

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
