<?php

declare(strict_types=1);

namespace App\Actions\Conversation;

use App\Contracts\LanguageCatalog;
use App\DTO\TurnInput;
use App\Enums\TurnKind;
use App\Exceptions\ScenarioUnavailable;
use App\Exceptions\UnsupportedLanguage;
use App\Models\Conversation;
use App\Models\Scenario;
use App\Models\Turn;
use App\Models\User;

final readonly class StartConversation
{
    public function __construct(private SendTurn $sendTurn, private LanguageCatalog $languages) {}

    /**
     * Starts in `$language`, or in the language the learner studies when the client sends none
     * (older app versions). The language is kept on the conversation, so switching languages later
     * never changes a conversation already under way.
     *
     * @return array{0: Conversation, 1: Turn}
     *
     * @throws ScenarioUnavailable|UnsupportedLanguage
     */
    public function __invoke(User $user, string $scenarioId, ?string $language = null): array
    {
        $language ??= $user->profile()->value('target_language') ?? $this->languages->default()->code;

        if (! $this->languages->isAvailable($language)) {
            throw new UnsupportedLanguage($language);
        }

        $scenario = Scenario::query()->where('active', true)->find($scenarioId);

        if ($scenario === null || ! $scenario->isPlayableIn($language)) {
            throw new ScenarioUnavailable($scenarioId, $language);
        }

        $conversation = $user->conversations()->create([
            'scenario_id' => $scenario->id,
            'target_language' => $language,
            'started_at' => now(),
        ]);

        return [$conversation, ($this->sendTurn)($conversation, new TurnInput(TurnKind::Opening))];
    }
}
