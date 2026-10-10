<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TurnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transcript' => $this->transcript,
            'reply' => $this->text,
            'emotion' => $this->emotion,
            'audio_url' => $this->audio_url,
            'audio_ms' => $this->audio_ms,
        ];
    }
}
