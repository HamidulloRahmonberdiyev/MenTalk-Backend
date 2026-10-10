<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\TurnInput;
use App\Enums\TurnKind;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTurnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required_without:audio', 'string', 'max:500'],
            'audio' => ['required_without:text', 'string', 'max:4000000'],
            'mime' => ['required_with:audio', 'in:audio/mp4,audio/m4a,audio/aac,audio/mpeg'],
        ];
    }

    public function toInput(): TurnInput
    {
        return $this->filled('audio')
            ? new TurnInput(TurnKind::Audio, audio: $this->string('audio')->toString(), mime: $this->string('mime')->toString())
            : new TurnInput(TurnKind::Text, text: trim($this->string('text')->toString()));
    }
}
