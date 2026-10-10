<?php

declare(strict_types=1);

namespace App\Services\Conversation;

use App\Enums\TurnRole;
use App\Models\Conversation;
use App\Models\Turn;

final class Transcript
{
    public static function of(Conversation $conversation): string
    {
        return $conversation->turns()->orderBy('id')->get(['role', 'text'])
            ->map(fn (Turn $turn) => ($turn->role === TurnRole::Tutor ? 'Tutor' : 'Learner').': '.$turn->text)
            ->implode("\n");
    }
}
