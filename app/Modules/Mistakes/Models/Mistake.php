<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Models;

use App\Modules\Account\Models\User;
use App\Modules\Conversation\Enums\MistakeCategory;
use App\Modules\Vocabulary\Enums\ReviewRating;
use App\Modules\Vocabulary\Enums\WordStatus;
use App\Modules\Vocabulary\Support\ReviewState;
use App\Modules\Vocabulary\Support\Srs;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One mistake in a learner's notebook, with the schedule it is practised on. The same mistake
 * made again is this row again (`occurrences`), never a new one.
 *
 * @method static Builder<self> active()
 */
final class Mistake extends Model
{
    use HasUlids;

    protected $table = 'user_mistakes';

    protected $guarded = [];

    // The column defaults, so a freshly created model knows its schedule without a reload.
    protected $attributes = ['status' => 'new', 'ease' => Srs::START_EASE, 'interval_days' => 0, 'repetitions' => 0, 'lapses' => 0, 'occurrences' => 1];

    protected function casts(): array
    {
        return [
            'category' => MistakeCategory::class,
            'status' => WordStatus::class,
            'ease' => 'float',
            'interval_days' => 'integer',
            'repetitions' => 'integer',
            'lapses' => 'integer',
            'occurrences' => 'integer',
            'first_seen_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
            'due_at' => 'immutable_datetime',
            'last_reviewed_at' => 'immutable_datetime',
            'dismissed_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Mistakes still in the notebook: the learner has not set them aside as "not a mistake". */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('dismissed_at');
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

    /**
     * Made again. Practised before, that counts as forgetting it; either way it is due at once.
     * A newer note replaces the old one: it is in the learner's current interface language.
     */
    public function recur(CarbonImmutable $at, string $note, string $noteLanguage, MistakeCategory $category, ?string $conversationId): void
    {
        $this->occurrences++;

        if ($this->last_seen_at === null || $at->isAfter($this->last_seen_at)) {
            $this->last_seen_at = $at;
            $this->conversation_id = $conversationId ?? $this->conversation_id;

            if ($note !== '') {
                $this->fill(['note' => $note, 'note_language' => $noteLanguage]);
            }
        }

        if ($category !== MistakeCategory::Other) {
            $this->category = $category;
        }

        if ($this->status !== WordStatus::New) {
            $this->applyReviewState(Srs::review($this->reviewState(), ReviewRating::Again, $at));
        }

        if ($this->due_at === null || $this->due_at->isAfter($at)) {
            $this->due_at = $at;
        }

        $this->save();
    }
}
