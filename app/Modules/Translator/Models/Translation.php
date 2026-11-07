<?php

declare(strict_types=1);

namespace App\Modules\Translator\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * A translator answer shared by every learner: the same text in the same direction reaches the AI
 * once and is served from here afterwards. `result` holds the API response body (`TranslationResult`).
 *
 * `model:prune` drops answers nobody asked for again, which is mostly half-typed text from live
 * translation.
 */
final class Translation extends Model
{
    use MassPrunable;

    /** Days an answer that was never served again is kept. */
    public const UNUSED_RETENTION_DAYS = 30;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return self::query()->where('hits', 0)->where('created_at', '<', now()->subDays(self::UNUSED_RETENTION_DAYS));
    }
}
