<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Listeners;

use App\Modules\Conversation\Events\LearnerMadeMistakes;
use App\Modules\Mistakes\Actions\RecordMistakes;
use Throwable;

final readonly class CollectMistakes
{
    public function __construct(private RecordMistakes $record) {}

    public function handle(LearnerMadeMistakes $event): void
    {
        // The notebook is a by-product of the conversation: a failure here is reported, but the
        // tutor's reply the learner is waiting for still goes out.
        try {
            ($this->record)($event->userId, $event->language, $event->noteLanguage, $event->conversationId, $event->mistakes);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
