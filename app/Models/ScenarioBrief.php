<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How a scenario is played in one language: the place and the tutor's role ("A café in London.
 * You are {tutor}, the waitress.") and where to steer the talk next. Placeholders are filled by
 * `PromptRenderer`, so one brief can name the tutor of its language without repeating it.
 */
final class ScenarioBrief extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }
}
