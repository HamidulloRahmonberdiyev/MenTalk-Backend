<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Models;

use App\Modules\Speech\Enums\VoiceGender;
use App\Modules\Speech\Support\GeminiVoices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A voice learners can pick for the tutor, kept by admins in the panel. Its gender always follows the
 * Gemini voice, so the list can never call a male voice female.
 *
 * @property array<string, string> $name
 * @property string $voice
 * @property VoiceGender $gender
 */
final class TutorVoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'gender' => VoiceGender::class,
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (TutorVoice $voice): void {
            $voice->gender = GeminiVoices::gender($voice->voice) ?? $voice->gender ?? VoiceGender::Female;
        });
    }

    /** @param  Builder<TutorVoice>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
