<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Models;

use App\Modules\Account\Models\User;
use App\Modules\Vocabulary\Enums\WordSource;
use App\Modules\Vocabulary\Enums\WordStatus;
use App\Modules\Vocabulary\Support\ReviewState;
use App\Modules\Vocabulary\Support\Srs;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A word on a learner's list with its review schedule. Deleted rows are tombstones for sync:
 * `synced_at` says when a row last changed on the server, `updated_at` when the learner changed it.
 */
final class UserWord extends Pivot
{
    use HasUlids, SoftDeletes;

    protected $table = 'user_words';

    // The column defaults, so a freshly created model knows its schedule without a reload.
    protected $attributes = ['status' => 'new', 'ease' => Srs::START_EASE, 'interval_days' => 0, 'repetitions' => 0, 'lapses' => 0];

    protected function casts(): array
    {
        return [
            'status' => WordStatus::class,
            'source' => WordSource::class,
            'ease' => 'float',
            'interval_days' => 'integer',
            'repetitions' => 'integer',
            'lapses' => 'integer',
            'due_at' => 'immutable_datetime',
            'last_reviewed_at' => 'immutable_datetime',
            'synced_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /** The meaning this learner saved, in the language it was explained in. */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'translation_word_id');
    }

    public function reviewState(): ReviewState
    {
        return new ReviewState($this->status, $this->ease, $this->interval_days, $this->repetitions, $this->lapses, $this->due_at);
    }

    public function applyReviewState(ReviewState $state): void
    {
        $this->fill([
            'status' => $state->status,
            'ease' => $state->ease,
            'interval_days' => $state->intervalDays,
            'repetitions' => $state->repetitions,
            'lapses' => $state->lapses,
            'due_at' => $state->dueAt,
        ]);
    }
}
