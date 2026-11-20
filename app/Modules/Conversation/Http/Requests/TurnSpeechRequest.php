<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Requests;

use App\Modules\Conversation\Models\Turn;
use Illuminate\Foundation\Http\FormRequest;

final class TurnSpeechRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Part of the line to voice; the whole line when left out.
            'text' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /** The text to voice, or null when it is not part of the line. */
    public function spokenPartOf(Turn $turn): ?string
    {
        $text = trim((string) ($this->validated('text') ?? ''));

        if ($text === '') {
            return $turn->text;
        }

        return str_contains($turn->text, $text) ? $text : null;
    }
}
