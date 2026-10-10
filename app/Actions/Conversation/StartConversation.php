<?php

declare(strict_types=1);

namespace App\Actions\Conversation;

use App\DTO\TurnInput;
use App\Enums\TurnKind;
use App\Models\Conversation;
use App\Models\Scenario;
use App\Models\Turn;
use App\Models\User;

final readonly class StartConversation
{
    public function __construct(private SendTurn $sendTurn) {}

    /** @return array{0: Conversation, 1: Turn} */
    public function __invoke(User $user, string $scenarioId): array
    {
        $scenario = Scenario::query()->where('active', true)->findOrFail($scenarioId);

        $conversation = $user->conversations()->create([
            'scenario_id' => $scenario->id,
            'started_at' => now(),
        ]);

        return [$conversation, ($this->sendTurn)($conversation, new TurnInput(TurnKind::Opening))];
    }
}
