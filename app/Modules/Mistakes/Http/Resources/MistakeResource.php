<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Http\Resources;

use App\Modules\Mistakes\Models\Mistake;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Mistake */
final class MistakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'language' => $this->language,
            'said' => $this->said,
            'correct' => $this->correct,
            'note' => $this->note,
            'note_language' => $this->note_language,
            'category' => $this->category->value,
            'occurrences' => $this->occurrences,
            'status' => $this->status->value,
            'repetitions' => $this->repetitions,
            'lapses' => $this->lapses,
            'due_at' => $this->due_at?->toIso8601String(),
            'first_seen_at' => $this->first_seen_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'last_reviewed_at' => $this->last_reviewed_at?->toIso8601String(),
            'conversation_id' => $this->conversation_id,
        ];
    }
}
