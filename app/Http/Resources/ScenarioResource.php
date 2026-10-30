<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ScenarioBrief;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class ScenarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon,
            'tint' => $this->tint,
            'gradient' => $this->gradient,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            // The taught languages this scenario can be played in.
            'languages' => $this->whenLoaded('briefs', fn () => $this->briefs->map(fn (ScenarioBrief $brief) => $brief->language)->values()->all()),
        ];
    }
}
