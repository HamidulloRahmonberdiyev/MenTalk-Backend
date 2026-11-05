<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Conversation\Enums\TurnRole;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Turn;

final class Transcript
{
    public static function of(Conversation $conversation): string
    {
        return $conversation->turns()->orderBy('id')->get(['role', 'text'])
            ->map(fn (Turn $turn) => ($turn->role === TurnRole::Tutor ? 'Tutor' : 'Learner').': '.$turn->text)
            ->implode("\n");
    }
}
