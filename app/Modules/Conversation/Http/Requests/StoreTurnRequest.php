<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Requests;

use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\Enums\TurnKind;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTurnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required_without_all:audio,opening', 'string', 'max:500'],
            'audio' => ['required_without_all:text,opening', 'string', 'max:4000000'],
            'mime' => ['required_with:audio', 'in:audio/mp4,audio/m4a,audio/aac,audio/mpeg'],
            // Streamed turns only: the app can play the voice as several pieces.
            'clips' => ['sometimes', 'boolean'],
            // Streamed turns only: the tutor's first line of a conversation started with `defer_opening`.
            'opening' => ['sometimes', 'boolean'],
            // Streamed turns only: the same id on every retry of one turn, so it is answered once.
            'client_id' => ['sometimes', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    public function toInput(): TurnInput
    {
        $clientId = $this->filled('client_id') ? $this->string('client_id')->toString() : null;

        return match (true) {
            $this->boolean('opening') => new TurnInput(TurnKind::Opening, clientId: $clientId),
            $this->filled('audio') => new TurnInput(TurnKind::Audio, audio: $this->string('audio')->toString(), mime: $this->string('mime')->toString(), clientId: $clientId),
            default => new TurnInput(TurnKind::Text, text: trim($this->string('text')->toString()), clientId: $clientId),
        };
    }
}
