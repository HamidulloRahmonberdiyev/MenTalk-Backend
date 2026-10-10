<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'word' => $this->word,
            'translation' => $this->translation,
            'example' => $this->example,
            'form' => $this->form,
            'stage' => $this->stage,
            'streak' => $this->streak,
            'lapses' => $this->lapses,
            'due_at' => $this->due_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted' => $this->deleted_at !== null,
        ];
    }
}
