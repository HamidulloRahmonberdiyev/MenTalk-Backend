<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'serial' => $this->serial,
            'language' => $this->language,
            'level' => $this->level->value,
            'cefr' => $this->level->cefr(),
            'holder_name' => $this->holder_name,
            'speaking' => $this->speaking,
            'vocabulary' => $this->vocabulary,
            'grammar' => $this->grammar,
            'overall' => $this->overall,
            'issued_at' => $this->issued_at->toIso8601String(),
            'verify_url' => $this->verifyUrl(),
        ];
    }
}
