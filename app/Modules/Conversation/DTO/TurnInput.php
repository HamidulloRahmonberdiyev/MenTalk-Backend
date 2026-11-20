<?php

declare(strict_types=1);

namespace App\Modules\Conversation\DTO;

use App\Modules\Conversation\Enums\TurnKind;

final readonly class TurnInput
{
    /** @param  string|null  $clientId  the app's id for this turn; the same on every retry of it */
    public function __construct(
        public TurnKind $kind,
        public string $text = '',
        public string $audio = '',
        public string $mime = '',
        public ?string $clientId = null,
    ) {}
}
