<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewRating;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One answer in a review round. Append-only: the schedule in `user_words` is derived from these,
 * and they outlive the user and the word (see the migration).
 */
final class ReviewLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rating' => ReviewRating::class,
            'reviewed_at' => 'immutable_datetime',
            'duration_ms' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Review logs are append-only.'));
    }
}
