<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Events;

/**
 * The tutor corrected something the learner said. Raised once the turn is stored, so whoever
 * listens (the mistakes notebook) sees a turn that really happened.
 */
final readonly class LearnerMadeMistakes
{
    /**
     * @param  list<array{said?: mixed, correct?: mixed, note?: mixed, category?: mixed}>  $mistakes  as the model returned them
     */
    public function __construct(
        public string $userId,
        public string $language,
        public string $conversationId,
        /** The language the notes are written in. */
        public string $noteLanguage,
        public array $mistakes,
    ) {}
}
