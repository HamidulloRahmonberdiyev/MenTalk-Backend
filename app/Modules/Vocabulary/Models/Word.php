<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A word of the global dictionary, shared by all learners. Identified by language, normalized
 * spelling (`WordNormalizer`) and part of speech; create through `Dictionary`, which keeps that unique.
 */
final class Word extends Model
{
    /** The keys `details` may hold, per language. */
    public const DETAILS = [
        'ar' => ['root', 'plural', 'gender'],
        'ru' => ['stress', 'aspect'],
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'frequency_rank' => 'integer',
        ];
    }

    /** Words of other languages with the same meaning (or synonyms, within one language). */
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'word_translations', 'word_id', 'translated_word_id')->withPivot('sense_note');
    }

    public function examples(): HasMany
    {
        return $this->hasMany(WordExample::class);
    }
}
