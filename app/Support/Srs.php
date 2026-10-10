<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

final class Srs
{
    public const MAX_STAGE = 6;

    private const INTERVALS = [0, 28_800, 86_400, 259_200, 604_800, 1_382_400, 3_024_000];

    private const RETRY_SECONDS = 600;

    public static function review(CardState $card, bool $correct, CarbonImmutable $now): CardState
    {
        if (! $correct) {
            return new CardState(max(0, $card->stage - 2), $now->addSeconds(self::RETRY_SECONDS), 0, $card->lapses + 1);
        }

        if ($card->dueAt->isAfter($now)) {
            return new CardState($card->stage, $card->dueAt, $card->streak + 1, $card->lapses);
        }

        $stage = min(self::MAX_STAGE, $card->stage + 1);

        return new CardState($stage, $now->addSeconds(self::INTERVALS[$stage]), $card->streak + 1, $card->lapses);
    }
}
