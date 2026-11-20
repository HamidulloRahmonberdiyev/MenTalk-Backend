<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Conversation\Models\Conversation;
use App\Modules\Language\Contracts\LanguageCatalog;

/** The one voice a conversation's tutor speaks with, for every line and every retry of one. */
final readonly class ConversationVoice
{
    public function __construct(private LanguageCatalog $languages) {}

    public function of(Conversation $conversation): string
    {
        return $this->languages->get($conversation->target_language)->voice ?? config('ai.voice');
    }
}
