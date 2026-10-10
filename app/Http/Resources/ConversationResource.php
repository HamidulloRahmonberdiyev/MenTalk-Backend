<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scenario_id' => $this->scenario_id,
            'state' => $this->state->value,
            'score' => $this->score,
            'learner_turns' => $this->learner_turns,
            'duration_sec' => $this->duration_sec,
            'started_at' => $this->started_at->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
        ];
    }
}
