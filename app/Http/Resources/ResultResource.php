<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'conversation_id' => $this->conversation_id,
            'speech' => $this->speech,
            'vocabulary' => $this->vocabulary,
            'grammar' => $this->grammar,
            'overall' => $this->overall,
            'mistakes' => $this->mistakes,
            'new_words' => $this->new_words,
        ];
    }
}
