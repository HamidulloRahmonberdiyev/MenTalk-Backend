<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Resources;

use App\Modules\Vocabulary\Models\UserWord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserWord */
final class UserWordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'word' => $this->word->text,
            'language' => $this->word->language,
            'pos' => $this->word->pos ?: null,
            'transcription' => $this->word->transcription,
            'translation' => $this->translation?->text,
            'translation_language' => $this->translation?->language,
            'form' => $this->form,
            'context_sentence' => $this->context_sentence,
            'source' => $this->source,
            'status' => $this->status,
            'ease' => $this->ease,
            'interval_days' => $this->interval_days,
            'repetitions' => $this->repetitions,
            'lapses' => $this->lapses,
            'due_at' => $this->due_at->toIso8601String(),
            'last_reviewed_at' => $this->last_reviewed_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted' => $this->deleted_at !== null,
        ];
    }
}
