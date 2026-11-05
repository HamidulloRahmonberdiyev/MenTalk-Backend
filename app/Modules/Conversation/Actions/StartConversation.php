<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Modules\Account\Models\User;
use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Exceptions\ScenarioUnavailable;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Scenario;
use App\Modules\Conversation\Models\Turn;
use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\Exceptions\UnsupportedLanguage;

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
