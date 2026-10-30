<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Ai\PromptStore;
use Illuminate\Database\Eloquent\Model;

final class AiPrompt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** Wording for one taught language that overrides the shared prompt. */
    public function isVariant(): bool
    {
        return $this->key !== PromptStore::baseKey($this->key);
    }
}
